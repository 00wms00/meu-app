<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL armazena CHECK constraints por nome; precisamos dropar a antiga e recriar
        DB::statement('ALTER TABLE finance_expenses DROP CONSTRAINT IF EXISTS finance_expenses_status_check');

        DB::statement("
            ALTER TABLE finance_expenses
            ADD CONSTRAINT finance_expenses_status_check
            CHECK (status IN ('pendente', 'pago', 'pgoCC'))
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE finance_expenses DROP CONSTRAINT IF EXISTS finance_expenses_status_check');

        DB::statement("
            ALTER TABLE finance_expenses
            ADD CONSTRAINT finance_expenses_status_check
            CHECK (status IN ('pendente', 'pago'))
        ");
    }
};
