<?php

namespace App\Http\Controllers;

use setasign\Fpdi\Fpdi;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CertificadosImport;
use Illuminate\Support\Facades\Log;
use App\Models\Certificado;

class ControllerCert extends Controller
{
    public function gerarCertificados(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
            'template' => 'required|string',
        ]);

        try {
            Log::info('Iniciando a geração de certificados');

            // Carregando os dados do Excel
            $dados = Excel::toArray(new CertificadosImport, $request->file('file'));

            // Verificando se os dados são válidos
            if (empty($dados) || empty($dados[0])) {
                return response()->json(['erro' => 'O arquivo Excel está vazio ou mal formatado.'], 400);
            }

            $templateNome = basename($request->template);
            $templatePath = storage_path("app/templates/{$templateNome}");

            // Verificando se o template existe
            if (!file_exists($templatePath)) {
                Log::error('Template de certificado não encontrado.', ['template' => $templatePath]);
                return response()->json(['erro' => 'O template do certificado não foi encontrado.'], 400);
            }

            $certificadosGerados = [];
            foreach (array_slice($dados[0], 1) as $linha) {
                // Filtrando linhas vazias
                if (empty(array_filter($linha))) {
                    Log::warning('Linha vazia ignorada', ['linha' => $linha]);
                    continue;
                }

                // Verificando se as colunas necessárias estão presentes e preenchidas
                if (!isset($linha[0], $linha[1], $linha[3], $linha[4], $linha[5], $linha[6]) || 
                    empty($linha[0]) || empty($linha[1]) || empty($linha[3]) || empty($linha[4]) || empty($linha[5]) || empty($linha[6])) {
                    Log::warning('Linha inválida', ['linha' => $linha]);
                    continue;
                }

                // Tratando CPF e Data de Conclusão para gerar o hash
                $cpf = trim($linha[6]); // CPF
                $dataConclusao = trim($linha[4]); // Data Conclusão

                // Remover qualquer ponto, traço ou barra do CPF e Data
                $cpfNumerico = preg_replace('/\D/', '', $cpf); // Remove todos os caracteres não numéricos
                $dataConclusao = preg_replace('/\//', '', $dataConclusao); // Remove as barras (/)

                // Concatenando CPF e Data de Conclusão para gerar o hash
                $concatenacao = $cpfNumerico . $dataConclusao;
                $hash = md5($concatenacao); // Gerando o hash

                // Gerando o link para o QR Code
                $qrCodeUrl = url('/verificar_certificado/' . $hash);

                // Gerar o caminho do certificado PDF
                $outputPath = $this->gerarCertificadoPdf(
                    $linha[0], // Nome
                    $linha[1], // Curso
                    $linha[3], // Carga Horária
                    $linha[4], // Data Conclusão
                    $linha[5], // Unidade
                    $qrCodeUrl, // URL do QR Code
                    $templatePath
                );

                if (!$outputPath) {
                    Log::error('Erro ao gerar certificado', ['nome' => $linha[0], 'curso' => $linha[1]]);
                    continue;
                }

                // Inserir o certificado no banco de dados
                $certificado = Certificado::create([
                    'nome' => $linha[0],
                    'cpf' => $cpfNumerico, // Usando o CPF numérico
                    'email' => $linha[1], // Ajuste conforme necessário
                    'curso' => $linha[2],
                    'carga_horaria' => $linha[3],
                    'data_emissao' => now(),
                    'data_conclusao' => $linha[4],
                    'qr_code_path' => $qrCodeUrl,  // URL do QR Code
                    'certificado_path' => $outputPath, // Caminho do certificado PDF
                    'hash' => $hash,
                ]);

                // Deletar o QR Code após ser processado
                $qrCodePath = storage_path('app/qr_codes/qrcode_' . $hash . '.png');
                if (file_exists($qrCodePath)) {
                    unlink($qrCodePath); // Excluindo o QR Code gerado
                }

                $certificadosGerados[] = ['nome' => $linha[0], 'curso' => $linha[1], 'outputPath' => $outputPath];
            }

            return response()->json(['mensagem' => 'Certificados gerados!', 'certificados' => $certificadosGerados]);

        } catch (\Exception $e) {
            Log::error('Erro ao processar certificados', ['mensagem' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['erro' => 'Erro: ' . $e->getMessage()], 500);
        }
    }

    // Gerando o certificado PDF com QR Code embutido
    private function gerarCertificadoPdf($nomeAluno, $curso, $cargaHoraria, $dataConclusao, $unidade, $qrCodeUrl, $templatePath)
    {
        try {
            $certificadosDir = storage_path('app/certificados');
            $qrCodeDir = storage_path('app/qr_codes'); // Caminho correto para os QR Codes
            if (!is_dir($certificadosDir)) {
                mkdir($certificadosDir, 0755, true);
            }
            if (!is_dir($qrCodeDir)) {
                mkdir($qrCodeDir, 0755, true); // Garante que o diretório de QR Codes exista
            }

            // Gerando o QR Code em memória
            $qrCode = Builder::create()
                ->writer(new PngWriter())
                ->data($qrCodeUrl) // Passando o QR Code com o link do hash
                ->size(300)
                ->margin(10)
                ->build();

            // Salvando o QR Code no diretório especificado
            $qrCodePath = $qrCodeDir . '/qrcode_' . uniqid() . '.png';
            file_put_contents($qrCodePath, $qrCode->getString());

            // Criando o PDF com o QR Code embutido
            $pdf = new Fpdi();
            $pdf->AddPage('L');

            if (!file_exists($templatePath)) {
                Log::error('Template do certificado não encontrado ao tentar gerar o PDF.', ['template' => $templatePath, 'nomeAluno' => $nomeAluno, 'curso' => $curso]);
                return null;
            }
            $pdf->setSourceFile($templatePath);
            $template = $pdf->importPage(1);
            $pdf->useTemplate($template);

            // Adicionando nome, curso, carga horária, data e unidade
            $pdf->SetFont('Arial', 'B', 32, true);
            $pdf->SetXY(3.38 * 10, 7.15 * 10);
            $pdf->Cell(22.94 * 10, 1.62 * 10, $nomeAluno, 0, 1, 'C');

            $pdf->SetFont('Arial', 'B', 15, true);
            $pdf->SetXY(17.2, 89);
            $pdf->Cell(262.6, 24.2, "participou do " . $curso . " realizado de forma presencial no dia " . $dataConclusao, 0, 1, 'C');
            $pdf->SetXY(17.2, 98, $pdf->GetY());
            $pdf->Cell(262.6, 24.2, "na Faculdade Sao Leopoldo Mandic - " . $unidade, 0, 1, 'C');

            // Inserindo o QR Code
            $pdf->Image($qrCodePath, 245, 160, 35, 35); // Usando o arquivo do QR Code

            // Gerar o caminho do certificado PDF
            $outputPath = "$certificadosDir/certificado-" . uniqid() . ".pdf";
            $pdf->Output('F', $outputPath);

            // Remover o arquivo temporário do QR Code
            unlink($qrCodePath);

            return $outputPath;
        } catch (\Exception $e) {
            Log::error('Erro ao gerar certificado PDF', [
                'nomeAluno' => $nomeAluno,
                'curso' => $curso,
                'mensagem' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
}
