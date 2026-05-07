<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('finance_expenses', 'credit_card_id')) {
                $table->foreignId('credit_card_id')->nullable()->after('forma_pagamento')
                      ->constrained('finance_credit_cards')->nullOnDelete();
            }
            if (!Schema::hasColumn('finance_expenses', 'parcelas_total')) {
                $table->unsignedTinyInteger('parcelas_total')->default(1)->after('credit_card_id');
            }
            if (!Schema::hasColumn('finance_expenses', 'installment_id')) {
                $table->foreignId('installment_id')->nullable()->after('parcelas_total')
                      ->constrained('finance_installments')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('finance_expenses', function (Blueprint $table) {
            $table->dropForeign(['credit_card_id']);
            $table->dropForeign(['installment_id']);
            $table->dropColumn(['credit_card_id', 'parcelas_total', 'installment_id']);
        });
    }
};
