<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove FK antiga (aponta para finance_credit_cards, tabela vazia)
        DB::statement('ALTER TABLE finance_expenses DROP CONSTRAINT IF EXISTS finance_expenses_credit_card_id_foreign');

        // Recria apontando para credit_cards (tabela com os dados reais)
        DB::statement('ALTER TABLE finance_expenses ADD CONSTRAINT finance_expenses_credit_card_id_foreign
            FOREIGN KEY (credit_card_id) REFERENCES credit_cards(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE finance_expenses DROP CONSTRAINT IF EXISTS finance_expenses_credit_card_id_foreign');
        DB::statement('ALTER TABLE finance_expenses ADD CONSTRAINT finance_expenses_credit_card_id_foreign
            FOREIGN KEY (credit_card_id) REFERENCES finance_credit_cards(id) ON DELETE SET NULL');
    }
};
