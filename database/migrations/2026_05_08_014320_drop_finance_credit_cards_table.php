<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('finance_credit_cards');
    }

    public function down(): void
    {
        // Recria a tabela se necessário (estrutura original)
        Schema::create('finance_credit_cards', function ($table) {
            $table->id();
            $table->string('nome');
            $table->string('pessoa');
            $table->decimal('limite', 10, 2)->nullable();
            $table->integer('dia_fechamento');
            $table->integer('dia_vencimento');
            $table->string('cor')->default('#3b82f6');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }
};