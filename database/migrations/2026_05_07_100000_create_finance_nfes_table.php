<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Esta migration deve rodar ANTES das outras (100000 < 100001)
    public function up(): void
    {
        Schema::create('finance_nfes', function (Blueprint $table) {
            $table->id();
            $table->string('chave_acesso', 44)->nullable()->unique();
            $table->string('emitente')->nullable();
            $table->string('cnpj_emitente', 18)->nullable();
            $table->decimal('valor_total', 10, 2)->nullable();
            $table->date('data_emissao')->nullable();
            $table->enum('origem', ['mercado', 'veiculo', 'despesa', 'credito'])->default('despesa');
            $table->unsignedBigInteger('origem_id')->nullable(); // ID do registro vinculado
            $table->json('itens')->nullable(); // array de itens da nota
            $table->string('url_consulta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_nfes');
    }
};
