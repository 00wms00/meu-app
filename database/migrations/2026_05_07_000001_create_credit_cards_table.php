<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_cards', function (Blueprint $table) {
            $table->id();
            $table->string('nome');                     // Ex: NU WIL, NU MAY
            $table->string('bandeira')->default('visa'); // visa, mastercard, elo, amex, hipercard
            $table->string('pessoa');                   // WIL, MAY, compartilhado
            $table->decimal('limite', 10, 2)->nullable();
            $table->integer('dia_vencimento');          // 1-31
            $table->integer('dia_fechamento');          // 1-31
            $table->string('cor')->default('#6366f1');  // cor do card
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_cards');
    }
};
