<?php
// Esta migration foi substituída — as tabelas já existem via 100004/100005.
// Apenas adiciona os campos que faltavam em finance_credit_purchases.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('finance_credit_purchases', 'pessoa')) {
            Schema::table('finance_credit_purchases', function (Blueprint $table) {
                $table->string('pessoa')->default('WIL')->after('categoria');
            });
        }

        if (!Schema::hasColumn('finance_credit_purchases', 'observacao')) {
            Schema::table('finance_credit_purchases', function (Blueprint $table) {
                $table->string('observacao')->nullable()->after('data_compra');
            });
        }
    }

    public function down(): void
    {
        Schema::table('finance_credit_purchases', function (Blueprint $table) {
            $table->dropColumn(['pessoa', 'observacao']);
        });
    }
};