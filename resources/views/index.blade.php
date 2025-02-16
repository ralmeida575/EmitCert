<!DOCTYPE html>
<html lang="pt-BR">
<head>
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
</body>
</html>