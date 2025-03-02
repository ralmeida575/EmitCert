<?php

namespace App\Http\Controllers;

use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\IOFactory;
use setasign\Fpdi\Fpdi;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel as LaravelExcel;    
use App\Imports\CertificadosImport;
use Illuminate\Support\Facades\Log;
use App\Models\Certificado;
use Carbon\Carbon;
use App\Models\EmissaoCertificadoArquivo;
use Illuminate\Support\Facades\Mail;
use App\Mail\CertificadoEnviado;
use Aws\Sqs\SqsClient;



class ControllerCert extends Controller
{
    private $sqsClient;
    private $queueUrl;
    private $uuidArquivo;

    public function __construct()
    {
        $this->sqsClient = new SqsClient([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'endpoint' => env('AWS_SQS_ENDPOINT', 'http://localhost:9324'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID', 'test'),
                'secret' => env('AWS_SECRET_ACCESS_KEY', 'test'),
            ],
        ]);
        
        $this->queueUrl = env('AWS_SQS_QUEUE', 'http://localhost:9324/000000000000/certificados');
    }

    public function gerarCertificados(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
            'template' => 'required|string',
        ]);
    
        $certificadosGerados = [];
        $erros = []; // Lista para armazenar erros
        $quantidadeCertificados = 0;
    
        try {
            $dados = LaravelExcel::toArray(new CertificadosImport, $request->file('file'));
            if (empty($dados) || empty($dados[0])) {
                return response()->json(['erro' => 'O arquivo Excel está vazio ou mal formatado.'], 400);
            }
    
            $templateNome = basename($request->template);
            $templatePath = storage_path("app/templates/{$templateNome}");
            if (!file_exists($templatePath)) {
                return response()->json(['erro' => 'O template do certificado não foi encontrado.'], 400);
            }
    
            foreach (array_slice($dados[0], 1) as $index => $linha) {
                Log::info('Linha recebida: ', $linha);
                if (empty(array_filter($linha))) {
                    Log::info('Linha vazia, ignorada.');
                    continue;
                }
    
                if (!isset($linha[0], $linha[1], $linha[3], $linha[4], $linha[5], $linha[6]) || 
                    empty($linha[0]) || empty($linha[1]) || empty($linha[3]) || empty($linha[4]) || empty($linha[5]) || empty($linha[6])) {
                    Log::info('Linha com dados insuficientes: ', $linha);
                    $erros[] = [
                        'linha' => $index + 2, // Adiciona 2 para representar a linha correta no Excel (começando em 1 e considerando o cabeçalho)
                        'nome' => $linha[0] ?? 'N/A',
                        'curso' => $linha[1] ?? 'N/A',
                        'erro' => 'Dados insuficientes'
                    ];
                    continue;
                }
    
                $cpf = trim($linha[6]);
                $dataConclusao = trim($linha[4]);
                $cpfNumerico = preg_replace('/\D/', '', $cpf);
                if (strlen($cpfNumerico) !== 11) {
                    $erros[] = [
                        'linha' => $index + 2,
                        'nome' => $linha[0] ?? 'N/A',
                        'curso' => $linha[1] ?? 'N/A',
                        'erro' => 'CPF inválido'
                    ];
                    continue;
                }
    
                try {
                    if (is_numeric($dataConclusao)) {
                        $dataConclusao = Date::excelToDateTimeObject($dataConclusao)->format('Y-m-d');
                        $dataConclusao = Carbon::createFromFormat('Y-m-d', $dataConclusao);
                    } else {
                        $dataConclusao = Carbon::createFromFormat('d/m/Y', $dataConclusao);
                    }
                } catch (\Exception $e) {
                    $erros[] = [
                        'linha' => $index + 2,
                        'nome' => $linha[0] ?? 'N/A',
                        'curso' => $linha[1] ?? 'N/A',
                        'erro' => 'Data de conclusão inválida'
                    ];
                    continue;
                }
    
                $concatenacao = $cpfNumerico . $dataConclusao;
                $hash = md5($concatenacao);
                $qrCodeUrl = url('/verificar_certificado/' . $hash);
                $uuidArquivo = (string) \Str::uuid();
    
                $outputPath = $this->gerarCertificadoPdf(
                    $linha[0], 
                    $linha[1], 
                    $linha[3], 
                    $dataConclusao, 
                    $linha[5], 
                    $qrCodeUrl, 
                    $templatePath,
                    $hash
                );
    
                if (!$outputPath) {
                    $erros[] = [
                        'linha' => $index + 2,
                        'nome' => $linha[0] ?? 'N/A',
                        'curso' => $linha[1] ?? 'N/A',
                        'erro' => 'Erro ao gerar PDF'
                    ];
                    continue;
                }
    
                try {
                    Mail::to($linha[2])->send(new CertificadoEnviado($linha[0], $linha[1], $outputPath, $hash));
                    Log::info('E-mail enviado para: ' . $linha[2]);
    
                    Certificado::create([

                        'nome' => $linha[0],
                        'cpf' => $cpfNumerico,
                        'email' => $linha[2],
                        'curso' => $linha[1],
                        'carga_horaria' => $linha[3],
                        'unidade' => $linha[5],
                        'data_emissao' => now(),
                        'data_conclusao' => $dataConclusao,
                        'qr_code_path' => $qrCodeUrl,
                        'certificado_path' => $outputPath,
                        'hash' => $hash,
                    ]);

                    $this->enviarParaSqs([
                        'nome' => $linha[0],
                        'curso' => $linha[1],
                        'carga_horaria' => $linha[3],
                        'cpf' => $cpfNumerico,
                        'data_conclusao' => $dataConclusao->format('Y-m-d'),
                        'arquivo_uuid' => $uuidArquivo,
                    ]);

                    $certificadosGerados[] = ['nome' => $linha[0], 'curso' => $linha[1], 'outputPath' => $outputPath];
                    $quantidadeCertificados++;
                } catch (\Exception $e) {
                    Log::error('Erro ao enviar e-mail para ' . $linha[2] . ': ' . $e->getMessage());
                    $erros[] = [
                        'linha' => $index + 2,
                        'nome' => $linha[0] ?? 'N/A',
                        'curso' => $linha[1] ?? 'N/A',
                        'erro' => 'Erro ao enviar e-mail'
                    ];
                    continue;
                }
            }
    
            EmissaoCertificadoArquivo::create([
                'nomeArquivo' => $request->file('file')->getClientOriginalName(),
                'qtdeCertificados' => $quantidadeCertificados,
                'status' => 'pendente',
                'dataArquivo' => now(),
            ]);
    
            return response()->json([
                'status' => 'success', 
                'mensagem' => 'Certificados gerados!',
                'certificados' => $certificadosGerados,
                'quantidadeCertificados' => $quantidadeCertificados,
                'erros' => $erros // Retorna a lista de erros
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'mensagem' => 'Erro: ' . $e->getMessage()
            ], 500);
        }
    }
    

    private function gerarCertificadoPdf($nomeAluno, $curso, $cargaHoraria, $dataConclusao, $unidade, $qrCodeUrl, $templatePath, $hash)
    {
        try {
            $certificadosDir = storage_path('app/certificados');
            $qrCodeDir = storage_path('app/qr_codes');
            if (!is_dir($certificadosDir)) {
                mkdir($certificadosDir, 0755, true);
            }
            if (!is_dir($qrCodeDir)) {
                mkdir($qrCodeDir, 0755, true);
            }

            $qrCode = Builder::create()
                ->writer(new PngWriter())
                ->data($qrCodeUrl)
                ->size(300)
                ->margin(10)
                ->build();

            $qrCodePath = $qrCodeDir . '/qrcode_' . uniqid() . '.png';
            file_put_contents($qrCodePath, $qrCode->getString());

            $pdf = new Fpdi();
            $pdf->AddPage('L');
            $pdf->setSourceFile($templatePath);
            $template = $pdf->importPage(1);
            $pdf->useTemplate($template);
            $pdf->SetFont('Arial', 'B', 32, true);
            $pdf->SetXY(3.38 * 10, 7.15 * 10);
            $pdf->Cell(22.94 * 10, 1.62 * 10, $nomeAluno, 0, 1, 'C');
            $pdf->SetFont('Arial', 'B', 15, true);
            Carbon::setLocale('pt_BR');
            $dataConclusao = Carbon::parse($dataConclusao);
            $dataFormatada = $dataConclusao->translatedFormat('j \d\e F \d\e Y');
            $pdf->SetXY(17.2, 89);
            $pdf->Cell(262.6, 24.2, "Participou do Curso de " . $curso . " realizado de forma presencial no dia " . $dataFormatada, 0, 1, 'C');
            $pdf->SetXY(17.2, 92);
            $pdf->SetXY(17.2, 98, $pdf->GetY());
            $pdf->Cell(262.6, 24.2, "na Faculdade Sao Leopoldo Mandic - " . $unidade, 0, 1, 'C');
            $pdf->Image($qrCodePath, 245, 160, 35, 35);

            /*   Coloca o hash abaixo do QR code
            $pdf->SetFont('Arial', 'I', 10); 
            $pdf->SetXY(245, 195);  
            $pdf->Cell(30, 10, 'Código de Validação: ' . $hash, 0, 1, 'C'); */

            $outputPath = "$certificadosDir/certificado-" . uniqid() . ".pdf";
            $pdf->Output('F', $outputPath);
            unlink($qrCodePath);
            return $outputPath;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function validarCertificado($hash)
    {
        $certificado = Certificado::where('hash', $hash)->first();
        if (!$certificado) {
            return response()->json(['erro' => 'Certificado não encontrado.'], 404);
        }
        return view('validar-certificado', [
            'certificado' => $certificado
        ]);
    }   

    public function download($hash)
    {
        $certificado = Certificado::where('hash', $hash)->firstOrFail();

        return Storage::disk('s3')->download($certificado->certificado_path);
    }

    private function enviarParaSqs(array $payload)
    {
        try {
            $result = $this->sqsClient->sendMessage([
                'QueueUrl' => $this->queueUrl,
                'MessageBody' => json_encode($payload),
            ]);
            Log::info('Mensagem enviada para SQS: ' . json_encode($payload) . ' | MessageId: ' . $result['MessageId']);
        } catch (\Exception $e) {
            Log::error('Erro ao enviar mensagem para SQS: ' . $e->getMessage());
        }
    }
}
