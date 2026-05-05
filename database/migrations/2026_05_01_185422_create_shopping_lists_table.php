<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Listas de compras
        Schema::create('shopping_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nome');
            $table->boolean('ativa')->default(true);
            $table->date('data_compra')->nullable();
            $table->decimal('valor_total', 10, 2)->default(0);
            $table->timestamps();
        });

        // Itens da lista
        Schema::create('shopping_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopping_list_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->string('nome'); // nome do item
            $table->decimal('quantidade', 10, 3)->default(1);
            $table->string('unidade', 5)->default('UN');
            $table->decimal('preco_estimado', 10, 2)->nullable(); // preço médio histórico
            $table->boolean('comprado')->default(false);
            $table->integer('ordem')->default(0);
            $table->text('observacao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopping_list_items');
        Schema::dropIfExists('shopping_lists');
    }
};
