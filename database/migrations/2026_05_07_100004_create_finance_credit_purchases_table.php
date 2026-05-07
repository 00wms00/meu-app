<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Compras no crédito — cada compra gera N parcelas na tabela finance_installments
        Schema::create('finance_credit_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_card_id')->constrained('finance_credit_cards')->onDelete('cascade');
            $table->string('descricao');
            $table->string('categoria')->nullable();
            $table->decimal('valor_total', 10, 2);
            $table->unsignedTinyInteger('parcelas_total')->default(1);
            $table->date('data_compra');
            $table->unsignedBigInteger('nfe_id')->nullable();
            $table->string('observacao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_credit_purchases');
    }
};
