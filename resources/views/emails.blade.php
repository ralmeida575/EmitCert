<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Seu Certificado de Conclusão</title>
  <style>
    body {
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
      background-color: #f5f5f5;
      margin: 0;
      padding: 20px;
      color: #333;
    }
    .container {
      max-width: 600px;
      margin: 0 auto;
      background: #ffffff;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .header {
      background: #252f67;
      padding: 20px;
      text-align: center;
    }
    .header img {
      max-width: 150px;
      margin-bottom: 10px;
    }
    .header h1 {
      color: #ffffff;
      margin: 0;
      font-size: 24px;
    }
    .content {
      padding: 20px;
      text-align: center;
    }
    .content h2 {
      color: #252f67;
      font-size: 22px;
      margin-bottom: 10px;
    }
    .content p {
      font-size: 16px;
      line-height: 1.5;
      margin: 10px 0;
    }
    .footer {
      background: #f0f0f0;
      text-align: center;
      padding: 15px;
      font-size: 12px;
      color: #777;
    }
    .button {
      display: inline-block;
      background-color: #252f67;
      color: #ffffff;
      padding: 10px 20px;
      text-decoration: none;
      border-radius: 4px;
      margin-top: 20px;
    }
  p,small {
    color: #333 !important;
    text-decoration: none !important;
  }

  </style>
</head>
<body>
  <div class="container">
    <!-- Cabeçalho com logo -->
    <div class="header">
        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSqllfNihEGukGwfcxEQ1PBGViCreJ3zwJHow&s" alt="Logo" class="logo">
        <h1>Faculdade São Leopoldo Mandic</h1>
    </div>

    <!-- Conteúdo -->
    <div class="content">
      <h2>Olá, {{ $nome }}!</h2>
      <p>Parabéns por concluir o curso de <strong>{{ $curso }}</strong> com excelência!</p>
      <p>É com muito orgulho que enviamos seu certificado de conclusão. Em anexo, você encontrará o documento para download.</p>
      <p>Além disso, você pode validar as informações do seu certificado online clicando no botão abaixo.</p>
      <a class="button" href="{{ url('verificar_certificado/' . $hash) }}" style="color: #ffffff; text-decoration: none; display: inline-block;">Página de Validação do Certificado</a><br><br>
      <small>Essa mesma <b>página</b> pode ser acessada posteriormente escaneando o QR Code impresso no certificado PDF.</small>
    </div>

    <!-- Rodapé -->
    <div class="footer">
      <p>© {{ date('Y') }} Faculdade São Leopoldo Mandic. Todos os direitos reservados.</p>
    </div>
  </div>
</body>
</html>
