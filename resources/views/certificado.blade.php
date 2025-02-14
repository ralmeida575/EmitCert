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
            background-color: #ffffff;
            padding: 40px;
        }
        .certificado {
            border: 10px solid #1A73E8;
            padding: 50px;
            width: 800px;
            margin: auto;
            position: relative;
        }
        h1 {
            font-size: 24px;
            color: #1A73E8;
        }
        .nome {
            font-size: 28px;
            font-weight: bold;
            margin-top: 20px;
        }
        .texto {
            font-size: 18px;
            margin: 20px;
        }
        .qr-code {
            position: absolute;
            bottom: 20px;
            right: 20px;
        }
    </style>
</head>
<body>
    <div class="certificado">
        <h1>Certificado de Conclusão</h1>
        <p>Certificamos que</p>
        <div class="nome">{{ $nome }}</div>
        <p class="texto">Concluiu com êxito o curso de <strong>{{ $curso }}</strong>, 
        realizado em <strong>{{ $data }}</strong>.</p>
        <p>Este certificado foi gerado automaticamente e pode ser validado através do QR Code.</p>
        <img src="{{ public_path('qr_codes/'.$certificadoId.'.svg') }}" class="qr-code" width="100">
    </div>
</body>
</html>
