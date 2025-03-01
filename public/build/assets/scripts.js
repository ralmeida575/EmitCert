document.addEventListener('DOMContentLoaded', function() {
    // Exibe a notificação com o total de certificados gerados
    window.showNotification = function(type, quantidade) {
        const notificationContainer = document.getElementById('notification-container');
        const notificationContent = document.getElementById('notification-content');
        notificationContainer.className = 'notification-container ' + (type === 'success' ? 'success' : 'error');

        // Limpa o conteúdo anterior e exibe somente o total
        notificationContent.innerHTML = ''; 
        const totalDiv = document.createElement('div');
        totalDiv.style.fontWeight = 'bold';
        totalDiv.textContent = "Certificados Gerados e Enviados: " + quantidade;
        notificationContent.appendChild(totalDiv);

        // Exibe o container de notificação
        notificationContainer.style.display = 'block';

        // Adiciona botão de fechamento, se ainda não existir
        if (!notificationContainer.querySelector('.close-notification')) {
            const closeButton = document.createElement('span');
            closeButton.textContent = '×'; // Símbolo de fechamento
            closeButton.className = 'close-notification';
            closeButton.onclick = () => {
                notificationContainer.style.display = 'none';
            };
            notificationContainer.appendChild(closeButton);
        }
    };

    // Exibe a lista de certificados não gerados (erros) no container de erros
    window.showErrorCertificates = function(erros) {
        const errorContainer = document.getElementById('error-container');
        const errorContent = document.getElementById('error-content');
        errorContent.innerHTML = ''; // Limpa o conteúdo anterior

        if (erros && erros.length > 0) {
            const ul = document.createElement('ul');
            erros.forEach(erro => {
                const li = document.createElement('li');
                // Exibe: Linha X: [mensagem de erro] (Nome: Y, Curso: Z)
                li.textContent = `Linha ${erro.linha}: ${erro.erro} (${erro.nome}, ${erro.curso})`;
                ul.appendChild(li);
            });
            errorContent.appendChild(ul);
        } else {
            errorContent.textContent = 'Nenhum certificado com erro.';
        }

        errorContainer.style.display = 'block';

        // Adiciona botão de fechamento, se ainda não existir
        if (!errorContainer.querySelector('.close-notification')) {
            const closeButton = document.createElement('span');
            closeButton.textContent = '×';
            closeButton.className = 'close-notification';
            closeButton.onclick = () => {
                errorContainer.style.display = 'none';
            };
            errorContainer.appendChild(closeButton);
        }
    };

    // Envia o formulário e processa a resposta
    window.enviarFormulario = async function(event) {
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
                message.className = "message success-message";

                // Exibe somente o total de certificados gerados
                showNotification('success', result.quantidadeCertificados);

                // Se houver erros, exibe a lista de erros no container separado
                if (result.erros && result.erros.length > 0) {
                    showErrorCertificates(result.erros);
                }

                // Remove a mensagem após 10 segundos
                setTimeout(() => {
                    message.style.display = 'none';
                }, 10000);
            } else {
                message.textContent = result.erro || 'Erro ao processar.';
                message.className = "message error";
                // Se houver erros no retorno, exibe-os também
                if (result.erros && result.erros.length > 0) {
                    showErrorCertificates(result.erros);
                }
            }
        } catch (error) {
            loading.style.display = 'none';
            message.style.display = 'block';
            message.textContent = 'Erro ao processar. Por favor, tente novamente.';
            message.className = "message error";
            console.error(error);
        }
    };

    // Atualiza o nome do arquivo selecionado
    window.updateFileName = function() {
        const fileInput = document.getElementById('file');
        const fileName = document.getElementById('file-name');
        fileName.textContent = fileInput.files.length > 0 ? fileInput.files[0].name : 'Selecione um arquivo...';
    };

    // Adiciona o evento de mudança ao input de arquivo
    document.getElementById('file').addEventListener('change', updateFileName);
});
