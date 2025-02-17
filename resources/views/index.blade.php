<!DOCTYPE html>
<html lang="pt-BR">
<head>
<<<<<<< HEAD
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/build/assets/styles.css">
    <title>GERADOR DE CERTIFICADOS</title>

    <style>
        /* Estilos para o loading */
        #loading {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 18px;
            z-index: 9999;
            text-align: center;
        }

        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #fff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin-bottom: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .message {
            display: none;
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }

        .message.success {
            background-color: #4CAF50;
            color: white;
        }

        .message.error {
            background-color: #f44336;
            color: white;
        }

        /* Outros estilos */
        .container {
            text-align: center;
            max-width: 500px;
            margin: 0 auto;
        }

        .logo {
            max-width: 200px;
            margin-bottom: 20px;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        input[type="file"], select {
            padding: 8px;
            width: 100%;
            max-width: 300px;
            margin-bottom: 10px;
        }

        button {
            padding: 10px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
        }

        button:hover {
            background-color: #0056b3;
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
            loading.style.display = 'block';

            try {
                const response = await fetch("/gerar-certificados", {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-CSRF-TOKEN": token
                    }
                });

                const result = await response.json();
                loading.style.display = 'none';
                message.style.display = 'block';

                if (response.ok) {
                    message.textContent = result.mensagem || 'Certificados gerados com sucesso!';
                    message.className = "message success";
                } else {
                    message.textContent = result.erro || 'Erro ao processar.';
                    message.className = "message error";
                }
            } catch (error) {
                loading.style.display = 'none';
                message.style.display = 'block';
                message.textContent = 'Erro ao processar. Por favor, tente novamente.';
                message.className = "message error";
                console.error(error);
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSqllfNihEGukGwfcxEQ1PBGViCreJ3zwJHow&s" alt="Logo" class="logo">
        <h1>Gerador de Certificados</h1>
        <form onsubmit="enviarFormulario(event)" enctype="multipart/form-data">
            @csrf
            <label for="file">Selecione o arquivo Excel :</label>
            <input type="file" name="file" id="file" accept=".xls,.xlsx" required>

            <label for="template">Escolha o modelo de certificado:</label>
            <select name="template" id="template" required>
                <option value="template_certificado_1.pdf">Grad. Odonto</option>
                <option value="template_certificado_2.pdf">Pós-Odonto</option>
                <option value="template_certificado_3.pdf">Mandic</option>
            </select>

            <button type="submit" style="padding: 10px 20px; font-size: 14px; max-width: 250px; margin-top: 20px; border-radius: 5px; background-color: #007BFF; color: white; border: none; cursor: pointer; text-align: center;">Gerar e Enviar Certificados</button>

            <!-- Loading Indicator -->
            <div id="loading">
                <div class="spinner"></div>
                Carregando...
            </div>

            <!-- Mensagens de Sucesso ou Erro -->
            <div class="message"></div>
        </form>
    </div>
=======
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="/build/assets/styles.css">
<title>GERADOR DE CERTIFICADOS</title>

<script>
async function enviarFormulario(event) {
event.preventDefault();
const form = document.querySelector("form");
const formData = new FormData(form);
const message = document.querySelector(".message");
const loading = document.querySelector("#loading");

const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
message.style.display = 'none';
loading.style.display = 'block';

try {
const response = await fetch("/gerar-certificados", {
method: "POST",
body: formData,
headers: {
"X-CSRF-TOKEN": token
}
});

const result = await response.json();
loading.style.display = 'none';

message.style.display = 'block';
if (response.ok) {
message.textContent = result.mensagem || 'Certificados gerados com sucesso!';
message.className = "message success";
} else {
message.textContent = result.erro || 'Erro ao processar.';
message.className = "message error";
}
} catch (error) {
loading.style.display = 'none';
message.style.display = 'block';
message.textContent = 'Erro ao processar. Por favor, tente novamente.';
message.className = "message error";
console.error(error);
}
}
</script>
</head>
<body>
<div class="container">
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
</div>
>>>>>>> 3fd43c9c03f8177bb0e5bff5a213da0373cbe19d
</body>
</html>

