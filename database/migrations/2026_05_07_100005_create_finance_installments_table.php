<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Parcelas geradas automaticamente ao cadastrar uma compra no crédito
        Schema::create('finance_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('finance_credit_purchases')->onDelete('cascade');
            $table->foreignId('credit_card_id')->constrained('finance_credit_cards')->onDelete('cascade');
            $table->unsignedTinyInteger('numero');      // 1, 2, 3...
            $table->unsignedTinyInteger('total');        // total de parcelas
            $table->decimal('valor', 10, 2);
            $table->date('mes_referencia');              // mês em que cai na fatura
            $table->enum('status', ['pendente', 'pago'])->default('pendente');
            $table->timestamps();

            $table->index(['credit_card_id', 'mes_referencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_installments');
    }
};
