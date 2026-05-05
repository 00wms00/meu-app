<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Criar a tabela normalmente com string
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nome'); // Usar string normal
            $table->string('unidade_padrao')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'nome']);
        });
        
        // Converter a coluna 'nome' para citext (case-insensitive)
        DB::statement('CREATE EXTENSION IF NOT EXISTS citext');
        DB::statement('ALTER TABLE products ALTER COLUMN nome TYPE citext');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
