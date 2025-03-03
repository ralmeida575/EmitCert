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
use Illuminate\Support\Str;




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
        'template' => 'required|string|in:template_certificado_1.pdf,template_certificado_2.pdf,template_certificado_3.pdf',
    ]);
    
    try {
        $dados = LaravelExcel::toArray(new CertificadosImport, $request->file('file'));
        if (empty($dados) || empty($dados[0])) {
            return response()->json(['erro' => 'O arquivo Excel está vazio ou mal formatado.'], 400);
        }

        $quantidadeCertificados = 0;

        foreach (array_slice($dados[0], 1) as $index => $linha) {
            if (empty(array_filter($linha))) {
                continue;
            }

            $cpf = trim($linha[6]);
            $dataConclusao = trim($linha[4]);
            $cpfNumerico = preg_replace('/\D/', '', $cpf);
            if (strlen($cpfNumerico) !== 11) {
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
                Log::error("Erro ao processar data de conclusão na linha {$index}: " . $e->getMessage());
                continue;
            }
            $concatenacao = $cpfNumerico . $dataConclusao;
            $hash = md5($concatenacao);
            $qrCodeUrl = url('/verificar_certificado/' . $hash);

            $certificadoPath = storage_path("app/certificados/{$hash}.pdf");

                $certificado = Certificado::create([
                'nome' => $linha[0],
                'cpf' => $cpfNumerico,
                'email' => $linha[2],
                'curso' => $linha[1],
                'carga_horaria' => $linha[3],
                'unidade' => $linha[5],
                'data_emissao' => now(),
                'data_conclusao' => $dataConclusao,
                'qr_code_path' => $qrCodeUrl,
                'certificado_path' => $certificadoPath,
                'hash' => $hash,
            ]);

            if (!$certificado) {
                Log::error("Erro ao salvar certificado para CPF: {$cpfNumerico}");
                continue; 
            }

            $payload = $certificado->toArray();
            $this->enviarParaSqs($payload);
            $quantidadeCertificados++;
        }

        EmissaoCertificadoArquivo::create([
            'arquivo_uuid' => Str::uuid(),
            'nomeArquivo' => $request->file('file')->getClientOriginalName(),
            'qtdeCertificados' => $quantidadeCertificados,
            'status' => 'pendente',
            'dataArquivo' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'mensagem' => 'Dados enviados para processamento!'
        ]);
    } catch (\Exception $e) {
        Log::error("Erro ao gerar certificados: " . $e->getMessage());
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

            if (!is_string($qrCodeUrl) || empty($qrCodeUrl)) {
                Log::error("Erro ao gerar QR Code: URL inválida. Valor de qrCodeUrl: " . json_encode($qrCodeUrl));
                return null;
            }
            Log::info("URL do QR Code: " . $qrCodeUrl);            
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
            Log::info("Salvando certificado em: " . $outputPath);
            $pdf->Output('F', $outputPath);
            unlink($qrCodePath);
            return $outputPath;
        } catch (\Exception $e) {
            Log::error("Erro ao gerar PDF: " . $e->getMessage());
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
        $payload['qr_code_url'] = $payload['qr_code_path'] ?? null; 
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

    public function receberWebhook(Request $request)
{
    $qrCodeUrl = $request->input('qr_code_url') ?? Certificado::where('hash', $request->input('hash'))->value('qr_code_path');

    if (!$qrCodeUrl) {
        Log::error("Erro ao gerar QR Code: URL inválida. Nenhuma URL encontrada para hash: " . $request->input('hash'));
        return response()->json(['erro' => 'URL do QR Code ausente'], 400);
    }

    Log::info('Webhook recebido:', $request->all());

    $arquivoUuid = $request->input('arquivo_uuid');

    Log::info("Atualizando status do arquivo_uuid: {$arquivoUuid}");
    EmissaoCertificadoArquivo::where('arquivo_uuid', $arquivoUuid)
        ->update(['status' => 'em_processamento']);

        $templateNome = basename($request->template);
        // O PDF nao é gerado quando recebe o nome do template via request
        $templatePath = storage_path("app/templates/template_certificado_1.pdf");
        Log::info("Caminho do template: " . $templatePath);
        
        if (!file_exists($templatePath)) {
            Log::error("Template não encontrado: " . $templatePath);
            return response()->json(['erro' => 'Template não encontrado'], 404);
        }
        
        Log::info("Caminho do template para gerar o certificado: " . $templatePath);
        $outputPath = $this->gerarCertificadoPdf(
        $request->input('nome'),
        $request->input('curso'),
        $request->input('carga_horaria'),
        $request->input('data_conclusao'),
        $request->input('unidade'),
        $request->input('qr_code_url'),
        $templatePath, 
        $request->input('hash')
    );

    if (!$outputPath) {
        Log::error('Erro ao gerar PDF para hash: ' . $request->input('hash'));
        return response()->json(['erro' => 'Falha ao gerar certificado'], 500);
    }

    Certificado::where('hash', $request->input('hash'))
        ->update(['certificado_path' => $outputPath]);


        
    return response()->json([
        'message' => 'Certificado gerado e enviado com sucesso!',
        'path' => $outputPath
    ], 200);
}

    
    
    
}
