<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_credit_cards', function (Blueprint $table) {
            $table->id();
            $table->string('nome');           // "Nubank Willian", "Nubank Mayara"
            $table->enum('pessoa', ['WIL', 'MAY', 'compartilhado'])->default('WIL');
            $table->decimal('limite', 10, 2)->nullable();
            $table->unsignedTinyInteger('dia_fechamento'); // dia do mês que fecha a fatura
            $table->unsignedTinyInteger('dia_vencimento'); // dia do mês que vence
            $table->string('cor', 7)->default('#6366f1'); // hex para exibição
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_credit_cards');
    }
};
