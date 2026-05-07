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
            $table->enum('pessoa', ['WIL', 'MAY', 'compartilhado']);
            $table->enum('tipo', ['salario', 'freelance', 'presente', 'aluguel', 'outros'])->default('salario');
            $table->decimal('valor', 12, 2);
            $table->date('mes_referencia');          // sempre o dia 1 do mês
            $table->date('data_recebimento')->nullable();
            $table->boolean('recorrente')->default(false);
            $table->string('observacao', 500)->nullable();
            $table->timestamps();

            $table->index(['mes_referencia', 'pessoa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_incomes');
    }
};
