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
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('descricao', 120);
            $table->unsignedInteger('km_ultimo_servico')->nullable();
            $table->unsignedInteger('intervalo_km');
            // km_alerta é calculado: km_ultimo_servico + intervalo_km
            // guardamos também na tabela para facilitar queries/ordenação
            $table->unsignedInteger('km_alerta')->virtualAs('km_ultimo_servico + intervalo_km')->nullable();
            $table->date('data_ultimo_servico')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_reminders');
    }
};
