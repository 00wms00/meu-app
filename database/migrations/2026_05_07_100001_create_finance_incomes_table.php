<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_incomes', function (Blueprint $table) {
            $table->id();
            $table->string('descricao');
            $table->enum('pessoa', ['WIL', 'MAY', 'compartilhado'])->default('WIL');
            $table->enum('tipo', ['salario', 'freelance', 'presente', 'aluguel', 'outros'])->default('salario');
            $table->decimal('valor', 10, 2);
            $table->date('mes_referencia'); // primeiro dia do mês: 2026-05-01
            $table->date('data_recebimento')->nullable();
            $table->boolean('recorrente')->default(false);
            $table->string('observacao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_incomes');
    }
};
