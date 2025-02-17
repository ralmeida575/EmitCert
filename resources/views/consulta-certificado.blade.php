<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validação de Certificado</title>
    <style>
        /* Estilos conforme o seu layout anterior */
    </style>
</head>
<body>

<div class="container">
    <div class="marca-dagua"></div>
    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSqllfNihEGukGwfcxEQ1PBGViCreJ3zwJHow&s" alt="Logo" class="logo">
    
    <h1>Verifique a Validade do Certificado</h1>
    
    <form method="POST" action="{{ route('certificado.validar') }}">
        @csrf
        <div>
            <label for="codigo">Código de Validação:</label>
            <input type="text" id="codigo" name="codigo" placeholder="Digite o código de validação" required>
        </div>
        
        <button type="submit">Validar</button>
    </form>

    @if (isset($certificado))
        <div class="certificado-info">
            <p><strong>Nome do Aluno:</strong> {{ $certificado->nome }}</p>
            <p><strong>Curso:</strong> {{ $certificado->curso }}</p>
            <p><strong>Carga Horária:</strong> {{ $certificado->carga_horaria }}</p>
            <p><strong>Data de Conclusão:</strong> {{ date('d/m/Y', strtotime($certificado->data_conclusao)) }}</p>
        </div>
    @elseif (isset($erro))
        <p class="erro">Certificado não encontrado ou código inválido. Verifique o código e tente novamente.</p>
    @endif
</div>

</body>
</html>
