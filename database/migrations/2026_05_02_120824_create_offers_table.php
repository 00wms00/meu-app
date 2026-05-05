<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('estabelecimento');
            $table->string('nome_produto');
            $table->decimal('preco_oferta', 10, 2);
            $table->string('unidade', 5)->default('UN');
            $table->decimal('quantidade', 10, 3)->default(1);
            $table->date('validade_inicio')->nullable();
            $table->date('validade_fim')->nullable();
            $table->string('fonte')->nullable();
            $table->text('observacao')->nullable();
            $table->boolean('ativa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
