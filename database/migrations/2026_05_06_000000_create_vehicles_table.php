<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('apelido');
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->unsignedSmallInteger('ano')->nullable();
            $table->string('placa', 10)->nullable();
            $table->string('tipo_combustivel', 20)->nullable();
            $table->unsignedInteger('km_atual')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
