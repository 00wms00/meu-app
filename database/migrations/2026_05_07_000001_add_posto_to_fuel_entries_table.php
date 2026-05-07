<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fuel_entries', function (Blueprint $table) {
            $table->string('posto', 100)->nullable()->after('tipo_combustivel');
        });
    }

    public function down(): void
    {
        Schema::table('fuel_entries', function (Blueprint $table) {
            $table->dropColumn('posto');
        });
    }
};
