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

            $dados = Excel::toArray(new CertificadosImport, $request->file('file'));

            if (empty($dados) || empty($dados[0])) {
                return response()->json(['erro' => 'O arquivo Excel está vazio.'], 400);
            }

            // Garantir que o nome do template seja seguro e sem caminhos inválidos
            $templateName = basename($request->template);
            $templatePath = storage_path("app/templates/{$templateName}");

            if (!file_exists($templatePath)) {
                Log::error('Template de certificado não encontrado.', ['template' => $templatePath]);
                return response()->json(['erro' => 'O template do certificado não foi encontrado.'], 400);
            }

            $certificadosGerados = [];
            foreach ($dados[0] as $linha) {
                if (!isset($linha[0], $linha[1], $linha[3]) || empty($linha[0]) || empty($linha[1]) || empty($linha[3])) {
                    Log::warning('Linha inválida', ['linha' => $linha]);
                    continue;
                }

                $qrCodeUrl = url('/verificar_certificado/' . md5($linha[3]));
                $outputPath = $this->gerarCertificadoPdf($linha[0], $linha[1], $qrCodeUrl, $templatePath);

                if (!$outputPath) {
                    Log::error('Erro ao gerar certificado', ['nome' => $linha[0], 'curso' => $linha[1]]);
                    continue;
                }

                $certificadosGerados[] = ['nome' => $linha[0], 'curso' => $linha[1], 'outputPath' => $outputPath];
            }

            return response()->json(['mensagem' => 'Certificados gerados!', 'certificados' => $certificadosGerados]);
        } catch (\Exception $e) {
            Log::error('Erro ao processar certificados', ['mensagem' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['erro' => 'Erro: ' . $e->getMessage()], 500);
        }
    }

    private function gerarCertificadoPdf($nomeAluno, $curso, $qrCodeUrl, $templatePath)
    {
        try {
            $qrCodeDir = storage_path('app/qr_codes');
            $certificadosDir = storage_path('app/certificados');

            if (!is_dir($qrCodeDir)) mkdir($qrCodeDir, 0755, true);
            if (!is_dir($certificadosDir)) mkdir($certificadosDir, 0755, true);

            $qrCode = Builder::create()
                ->writer(new PngWriter())
                ->data($qrCodeUrl)
                ->size(300)
                ->margin(10)
                ->build();

            $qrCodePath = "$qrCodeDir/qrcode_" . uniqid() . ".png";
            Storage::disk('local')->put("qr_codes/" . basename($qrCodePath), $qrCode->getString());

            $pdf = new Fpdi();
            $pdf->AddPage();

            if (!file_exists($templatePath)) {
                Log::error('Template do certificado não encontrado ao tentar gerar o PDF.', ['template' => $templatePath]);
                return null;
            }

            $pdf->setSourceFile($templatePath);
            $template = $pdf->importPage(1);    
            $pdf->useTemplate($template);

            $pdf->SetFont('Arial', 'B', 16);
            $pdf->SetXY(50, 60);
            $pdf->Cell(0, 10, 'Nome: ' . $nomeAluno, 0, 1);
            $pdf->SetXY(50, 80);
            $pdf->Cell(0, 10, 'Curso: ' . $curso, 0, 1);

            // Inserir QR Code
            $pdf->Image($qrCodePath, 150, 60, 30, 30);

            // Salvar certificado
            $outputPath = "$certificadosDir/certificado-" . uniqid() . ".pdf";
            $pdf->Output('F', $outputPath);

            // Remover QR Code temporário
            Storage::delete("qr_codes/" . basename($qrCodePath));

            return $outputPath;
        } catch (\Exception $e) {
            Log::error('Erro ao gerar certificado PDF', ['mensagem' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return null;
        }
    }
}
