<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCertificadosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('certificados', function (Blueprint $table) {
            $table->id(); // Cria a coluna 'id' como BIGINT UNSIGNED auto_increment

            $table->foreignId('emissao_certificado_arquivos_id')->nullable()->constrained()->onDelete('set null'); // Assumindo que seja uma chave estrangeira

            $table->string('nome', 255)->collation('utf8mb4_unicode_ci'); // Coluna para o nome
            $table->string('cpf', 14)->collation('utf8mb4_unicode_ci');  // Coluna para o CPF
            $table->string('email', 255)->collation('utf8mb4_unicode_ci'); // Coluna para o e-mail
            $table->string('curso', 255)->collation('utf8mb4_unicode_ci'); // Coluna para o nome do curso
            $table->integer('carga_horaria'); // Coluna para a carga horária
            $table->date('data_emissao'); // Coluna para a data de emissão
            $table->date('data_conclusao'); // Coluna para a data de conclusão
            $table->string('qr_code_path', 255)->nullable()->collation('utf8mb4_unicode_ci'); // Coluna para o caminho do QR Code
            $table->string('certificado_path', 255)->collation('utf8mb4_unicode_ci'); // Coluna para o caminho do certificado
            $table->timestamps(); // Cria as colunas 'created_at' e 'updated_at'

            $table->string('hash', 255)->nullable()->unique()->collation('utf8mb4_unicode_ci'); // Coluna para o hash, com chave única
            $table->string('unidade', 255)->collation('utf8mb4_unicode_ci'); // Coluna para a unidade

            // Definindo a collation para a tabela inteira
            $table->engine = 'InnoDB'; // Definir o mecanismo para InnoDB para garantir suporte a transações e foreign keys
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('certificados');
    }
}
