<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_reminders', function (Blueprint $table) {
            $table->unsignedSmallInteger('intervalo_meses')->nullable()->after('intervalo_km');
        });

        Schema::table('vehicle_expenses', function (Blueprint $table) {
            $table->unsignedInteger('km_servico')->nullable()->after('valor');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_reminders', function (Blueprint $table) {
            $table->dropColumn('intervalo_meses');
        });
        Schema::table('vehicle_expenses', function (Blueprint $table) {
            $table->dropColumn('km_servico');
        });
    }
};
