<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // ID do produto canônico (agrupador)
            $table->foreignId('canonical_product_id')->nullable()->constrained('products')->nullOnDelete();
            // Nome normalizado para busca
            $table->string('nome_normalizado')->nullable();
            // Palavras-chave extraídas
            $table->json('keywords')->nullable();
            // Se foi agrupado manualmente
            $table->boolean('is_canonical')->default(false);
        });
        
        // Índice para busca rápida
        Schema::table('products', function (Blueprint $table) {
            $table->index('nome_normalizado');
            $table->index('canonical_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('canonical_product_id');
            $table->dropColumn('nome_normalizado');
            $table->dropColumn('keywords');
            $table->dropColumn('is_canonical');
        });
    }
};
