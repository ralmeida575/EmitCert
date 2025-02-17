<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Certificado</title>


    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

        body {
            background-color: #252F67; /* Azul da sua página principal */
            color: white;
            font-family: 'Poppins', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background: #252F67;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            text-align: center;
            max-width: 800px;
            width: 100%;
            position: relative;
        }

        .marca-dagua {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.08;
            background: url("https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSqllfNihEGukGwfcxEQ1PBGViCreJ3zwJHow&s") no-repeat center center;
            background-size: contain;
            z-index: 0;
        }

        .logo {
            width: 180px;
            margin-bottom: 30px;
        }

        h1 {
            font-size: 2em;
            font-weight: 600;
        }

        .texto {
            font-size: 1.2em;
            color: white;
            position: relative;
            z-index: 2;
        }

        .nome {
            font-size: 1.6em;
            font-weight: bold;
            color: #FFD700; /* Dourado para dar destaque ao nome */
            margin: 15px 0;
            position: relative;
            z-index: 2;
        }

        .qr-code {
            margin-top: 20px;
            width: 140px;
            height: 140px;
            border: 3px solid white;
            padding: 5px;
            border-radius: 10px;
            background: white;
        }

        .rodape {
            font-size: 1em;
            color: white;
            margin-top: 15px;
            position: relative;
            z-index: 2;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="marca-dagua"></div>
    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSqllfNihEGukGwfcxEQ1PBGViCreJ3zwJHow&s" alt="Logo" class="logo">
    
    <h1>Certificado de Conclusão</h1>
    
    <p class="texto">Certificamos que</p>
    <div class="nome">{{ $certificado->nome }}</div>
    
    <p class="texto">
        Concluiu com êxito o curso de <strong>{{ $certificado->curso }}</strong>, 
        realizado em <strong>{{ date('d/m/Y', strtotime($certificado->data_conclusao)) }}</strong>.
    </p>

    <p class="texto">Este certificado pode ser validado através do QR Code abaixo:</p>

    <p class="rodape">Código de Validação: <strong>{{ $certificado->hash }}</strong></p>
</div>

</body>
</html>
