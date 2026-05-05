<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('chave', 44)->unique();
            $table->string('numero', 20)->nullable();
            $table->string('serie', 10)->nullable();
            $table->dateTime('data_emissao');
            $table->string('cnpj', 18)->nullable();
            $table->string('nome_estabelecimento')->nullable();
            $table->string('endereco_estabelecimento')->nullable();
            $table->integer('total_itens')->default(0);
            $table->decimal('valor_total', 10, 2)->nullable();
            $table->decimal('descontos', 10, 2)->nullable();
            $table->decimal('valor_pago', 10, 2)->nullable();
            $table->string('forma_pagamento')->nullable();
            $table->string('consumidor_cpf', 14)->nullable();
            $table->string('consumidor_nome')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};