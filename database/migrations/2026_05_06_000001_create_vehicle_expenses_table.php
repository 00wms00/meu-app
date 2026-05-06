<?php

use App\Models\InvoiceItem;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vehicle::class)->constrained()->cascadeOnDelete();
            $table->date('data');
            $table->string('tipo', 30); // combustivel, manutencao, seguro, impostos, pedagio, outros
            $table->decimal('valor', 10, 2);
            $table->string('descricao')->nullable();
            $table->foreignIdFor(InvoiceItem::class)->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_expenses');
    }
};
