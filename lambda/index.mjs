import { SQSClient, ReceiveMessageCommand, DeleteMessageCommand } from "@aws-sdk/client-sqs";

// Configuração do cliente SQS para apontar para o LocalStack
const sqs = new SQSClient({
  region: "us-east-1",
  endpoint: "http://localhost:9324", // URL do LocalStack
  credentials: { accessKeyId: "test", secretAccessKey: "test" }
});

const queueUrl = "http://localhost:9324/000000000000/certificados";

async function processMessages() {
  console.log("Aguardando mensagens da fila...");

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
          console.log("📩 Mensagem recebida:", message.Body);

          await sqs.send(new DeleteMessageCommand({
            QueueUrl: queueUrl,
            ReceiptHandle: message.ReceiptHandle,
          }));

          console.log("✅ Mensagem processada e removida da fila!");
        }
      } else {
        console.log("Nenhuma mensagem na fila.");
        await new Promise(resolve => setTimeout(resolve, 5000)); // Aguarda 5 segundos antes de tentar de novo
      }
    } catch (error) {
      console.error("❌ Erro ao processar mensagens:", error);
    }
  }
}

processMessages();
