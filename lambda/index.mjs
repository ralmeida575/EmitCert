import fetch from "node-fetch"; 
import { SQSClient, ReceiveMessageCommand, DeleteMessageCommand } from "@aws-sdk/client-sqs";

const LARAVEL_WEBHOOK_URL = "http://localhost:8000/webhook/certificados-processados";
const queueUrl = "http://localhost:9324/000000000000/default";

const sqs = new SQSClient({
  region: "us-east-1",
  endpoint: "http://localhost:9324", 
  credentials: { accessKeyId: "test", secretAccessKey: "test" }
});

async function processMessages() {
  console.log("Iniciando consumo da fila...");

  while (true) {
    const command = new ReceiveMessageCommand({
      QueueUrl: queueUrl,
      MaxNumberOfMessages: 1, 
      WaitTimeSeconds: 5,
    });

    try {
      const response = await sqs.send(command);

      if (response.Messages && response.Messages.length > 0) {
        for (const message of response.Messages) {
          try {
            console.log("Mensagem recebida:", message.Body);
            const body = JSON.parse(message.Body);

            const webhookResponse = await fetch(LARAVEL_WEBHOOK_URL, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({
                arquivo_uuid: body.arquivo_uuid, 
                nome: body.nome,
                curso: body.curso,
                carga_horaria: body.carga_horaria,
                data_conclusao: body.data_conclusao,
                unidade: body.unidade,
                qr_code_url: body.qr_code_url,
                template_path: body.template_path,
                hash: body.hash,
                email: body.email,
                certificado_path: body.certificado_path,
                template_nome: body.template_nome
              }),
            });

            if (webhookResponse.ok) {
              console.log("Webhook enviado com sucesso!");
            } else {
              console.error("Erro ao enviar para o Laravel:", await webhookResponse.text());
            }

            await sqs.send(new DeleteMessageCommand({
              QueueUrl: queueUrl,
              ReceiptHandle: message.ReceiptHandle,
            }));

            console.log("Mensagem processada e removida da fila!");
          } catch (error) {
            console.error("Erro ao processar mensagem:", error);
          }
        }
      } else {
        console.log("Nenhuma mensagem na fila.");
        await new Promise(resolve => setTimeout(resolve, 5000));
      }
    } catch (error) {
      console.error("Erro ao buscar mensagens na fila:", error);
    }
  }

}

processMessages();
