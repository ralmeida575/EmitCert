<?php

namespace App\Jobs;

use Aws\Sqs\SqsClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnviarParaSQS implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public $messageBody;

    /**
     * Create a new job instance.
     *
     * @param  array  $messageBody
     */
    public function __construct($messageBody)
    {
        $this->messageBody = $messageBody;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            // Configuração do cliente SQS
            $sqsClient = new SqsClient([
                'version'     => 'latest',
                'region'      => config('queue.connections.sqs.region', 'us-east-1'),
                'endpoint'    => config('queue.connections.sqs.endpoint', 'http://localhost:9324'),
                'credentials' => [
                    'key'    => config('queue.connections.sqs.key', 'test'),
                    'secret' => config('queue.connections.sqs.secret', 'test'),
                ]
            ]);

            // Envia a mensagem para a fila configurada no Laravel
            $result = $sqsClient->sendMessage([
                'QueueUrl'    => config('queue.connections.sqs.queue', 'http://localhost:9324/000000000000/certificados'),
                'MessageBody' => json_encode($this->messageBody),
            ]);

            Log::info('Mensagem enviada para o SQS', ['messageId' => $result->get('MessageId')]);
        } catch (\Exception $e) {
            Log::error('Erro ao enviar mensagem para o SQS', ['error' => $e->getMessage()]);
        }
    }
}
