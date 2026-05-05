<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('categoria')->nullable()->after('unidade_padrao');
        });
        
        // Índice para busca por categoria
        Schema::table('products', function (Blueprint $table) {
            $table->index('categoria');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('categoria');
        });
    }
};
