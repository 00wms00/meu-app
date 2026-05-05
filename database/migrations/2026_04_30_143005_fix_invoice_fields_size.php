<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Aumentar tamanho da chave para 50 (caso de chaves manuais)
            $table->string('chave', 50)->change();
            // Aumentar tamanho do número para 30
            $table->string('numero', 30)->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('chave', 44)->change();
            $table->string('numero', 20)->change();
        });
    }
};
