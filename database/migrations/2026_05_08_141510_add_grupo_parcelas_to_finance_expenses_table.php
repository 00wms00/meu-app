<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_expenses', function (Blueprint $table) {
            $table->uuid('grupo_parcelas')->nullable()->after('id');
            $table->index('grupo_parcelas');
        });
    }

    public function down(): void
    {
        Schema::table('finance_expenses', function (Blueprint $table) {
            $table->dropIndex(['grupo_parcelas']);
            $table->dropColumn('grupo_parcelas');
        });
    }
};