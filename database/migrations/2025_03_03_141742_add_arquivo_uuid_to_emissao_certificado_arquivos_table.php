<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('emissao_certificado_arquivos', function (Blueprint $table) {
            $table->uuid('arquivo_uuid')->after('id');  // ou outro tipo de dado conforme sua necessidade
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emissao_certificado_arquivos', function (Blueprint $table) {
            $table->dropColumn('arquivo_uuid');
        });
        
    }
};
