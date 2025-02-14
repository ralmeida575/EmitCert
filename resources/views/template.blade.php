<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
        }
        .content {
            padding: 50px;
            border: 2px solid #000;
            width: 80%;
            margin: auto;
        }
        h1 {
            font-size: 2.5em;
        }
        p {
            font-size: 1.5em;
        }
        .qr-code {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="content">
        <h1>Certificado de Conclusão</h1>
        <p>Certificamos que</p>
        <p><strong>{{ $dado['nome'] }}</strong></p>
        <p>concluiu o curso de <strong>{{ $dado['curso'] }}</strong></p>
        <p>com carga horária de <strong>{{ $dado['carga_horaria'] }}</strong></p>
        <p>Emitido em: {{ now()->format('d/m/Y') }}</p>

        <div class="qr-code">
            <img src="data:image/svg+xml;base64,{{ base64_encode(file_get_contents($qrCodePath)) }}" alt="QR Code">
        </div>
    </div>
</body>
</html>
