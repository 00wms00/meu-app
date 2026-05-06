<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('descricao', 150);
            $table->unsignedInteger('km_ultimo_servico');
            $table->unsignedInteger('intervalo_km');
            // km_alerta = km_ultimo_servico + intervalo_km (coluna gerada)
            $table->unsignedInteger('km_alerta')->storedAs('km_ultimo_servico + intervalo_km');
            $table->date('data_ultimo_servico')->nullable();
            $table->string('observacao', 255)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_reminders');
    }
};
