<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_credit_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_card_id')->constrained('credit_cards')->cascadeOnDelete();
            $table->string('descricao');
            $table->string('categoria')->nullable();
            $table->string('pessoa');           // WIL, MAY, compartilhado
            $table->decimal('valor_total', 10, 2);
            $table->unsignedTinyInteger('parcelas_total')->default(1);
            $table->date('data_compra');
            $table->string('observacao')->nullable();
            $table->timestamps();
        });

        Schema::create('finance_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('finance_credit_purchases')->cascadeOnDelete();
            $table->foreignId('credit_card_id')->constrained('credit_cards')->cascadeOnDelete();
            $table->unsignedTinyInteger('numero');   // 1
            $table->unsignedTinyInteger('total');    // 3
            $table->decimal('valor', 10, 2);
            $table->date('mes_referencia');          // primeiro dia do mês da fatura
            $table->string('status')->default('pendente'); // pendente | pago
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_installments');
        Schema::dropIfExists('finance_credit_purchases');
    }
};
