<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->date('data');
            $table->decimal('valor', 10, 2);
            $table->decimal('litros', 8, 3)->nullable()->comment('Litros abastecidos');
            $table->decimal('preco_por_litro', 8, 3)->nullable()->storedAs('CASE WHEN litros > 0 THEN ROUND(valor / litros, 3) ELSE NULL END');
            $table->integer('km_abastecimento')->nullable()->comment('Km no momento do abastecimento');
            $table->string('tipo_combustivel', 30)->nullable()->comment('gasolina, etanol, diesel, gnv, eletrico');
            $table->string('posto', 100)->nullable();
            $table->boolean('tanque_cheio')->default(false);
            $table->string('descricao', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_entries');
    }
};
