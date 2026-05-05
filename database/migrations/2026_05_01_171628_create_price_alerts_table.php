<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('preco_referencia', 10, 2); // preço base para comparação
            $table->decimal('preco_atual', 10, 2); // último preço registrado
            $table->decimal('variacao_percentual', 8, 2)->default(0); // % de variação
            $table->decimal('limite_alerta', 8, 2)->default(10); // % para disparar alerta
            $table->boolean('ativo')->default(true);
            $table->timestamp('ultimo_alerta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_alerts');
    }
};
