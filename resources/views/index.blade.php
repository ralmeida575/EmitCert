<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GERADOR DE CERTIFICADOS</title>
    <style>
        body {
            background-color: #252F67;
            color: white;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .logo {
            width: 100px;
            margin-bottom: 20px;
        }
        h1 {
            text-align: center;
            color: white;
            padding: 10px;
            font-size: 2em;
        }
        form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        button {
            background-color: #1A73E8;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #125bb5;
        }
        #loading {
            display: none;
            color: white;
            margin-top: 10px;
        }
        .message {
            color: green;
            font-size: 1.2em;
            display: none;
        }
        .error {
            color: red;
        }
    </style>
    <script>
        async function enviarFormulario(event) {
            event.preventDefault();
            const form = document.querySelector("form");
            const formData = new FormData(form);
            const message = document.querySelector(".message");
            const loading = document.querySelector("#loading");

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            message.style.display = 'none';
            loading.style.display = 'block';  // Exibe o carregamento

            try {
                const response = await fetch("/gerar_certificados", {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-CSRF-TOKEN": token
                    }
                });

                const result = await response.json();
                loading.style.display = 'none';  // Esconde o carregamento

                if (response.ok) {
                    message.style.display = 'block';
                    message.textContent = result.mensagem || 'Certificados gerados com sucesso!';
                    message.classList.remove("error");
                } else {
                    message.style.display = 'block';
                    message.textContent = result.erro || 'Erro ao processar.';
                    message.classList.add("error");
                }
            } catch (error) {
                loading.style.display = 'none';
                message.style.display = 'block';
                message.textContent = 'Erro ao processar. Por favor, tente novamente.';
                message.classList.add("error");
                console.error(error);
            }
        }
    </script>
</head>
<body>
    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSqllfNihEGukGwfcxEQ1PBGViCreJ3zwJHow&s" alt="Logo" class="logo">
    
    <h1>Gerador de Certificados</h1>
    <form onsubmit="enviarFormulario(event)" enctype="multipart/form-data">
        @csrf
        <label for="file">Selecione o arquivo Excel:</label>
        <input type="file" name="file" id="file" accept=".xls,.xlsx" required>

        <label for="template">Escolha o modelo de certificado:</label>
        <select name="template" id="template" required>
            <option value="template_certificado_1.pdf">Grad. Odonto</option>
            <option value="template_certificado_2.pdf">Pós-Odonto</option>
            <option value="template_certificado_3.pdf">Mandic</option>
        </select>

        <button type="submit">Gerar e Enviar Certificados</button>
        <div id="loading">Carregando...</div>
        <div class="message"></div>
    </form>
</body>
</html>
