<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_expenses', function (Blueprint $table) {
            // Cartao como forma de pagamento já está no ENUM via string,
            // só precisamos da FK e campos de parcelamento
            $table->foreignId('credit_card_id')->nullable()->after('forma_pagamento')
                  ->constrained('credit_cards')->nullOnDelete();
            $table->unsignedTinyInteger('parcelas_total')->default(1)->after('credit_card_id');
            $table->foreignId('installment_id')->nullable()->after('parcelas_total')
                  ->constrained('finance_installments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('finance_expenses', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\CreditCard::class);
            $table->dropColumn(['credit_card_id', 'parcelas_total', 'installment_id']);
        });
    }
};
