<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('descricao');
            $table->enum('tipo_despesa', ['fixa', 'variavel'])->default('variavel');
            $table->string('categoria')->nullable(); // Lazer, Carro, Roupas, Alimentação...
            $table->enum('forma_pagamento', ['debito', 'pix', 'dinheiro'])->default('pix');
            $table->enum('pessoa', ['WIL', 'MAY', 'compartilhado'])->default('WIL');
            $table->decimal('valor', 10, 2);
            $table->date('mes_referencia'); // competência
            $table->date('data_vencimento')->nullable(); // para fixas
            $table->date('data_pagamento')->nullable();
            $table->enum('status', ['pago', 'pendente'])->default('pendente');
            // Origem: pode vir de mercado, veículos ou ser manual
            $table->enum('origem', ['manual', 'mercado', 'veiculo'])->default('manual');
            $table->unsignedBigInteger('origem_id')->nullable(); // ID da invoice ou vehicle_expense
            $table->unsignedBigInteger('nfe_id')->nullable();
            $table->foreign('nfe_id')->references('id')->on('finance_nfes')->onDelete('set null');
            $table->string('observacao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_expenses');
    }
};
