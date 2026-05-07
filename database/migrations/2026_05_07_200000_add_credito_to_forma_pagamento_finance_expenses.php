<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PostgreSQL: remove o CHECK antigo e cria um novo incluindo 'credito'
        DB::statement('ALTER TABLE finance_expenses DROP CONSTRAINT IF EXISTS finance_expenses_forma_pagamento_check');
        DB::statement("ALTER TABLE finance_expenses ADD CONSTRAINT finance_expenses_forma_pagamento_check CHECK (forma_pagamento IN ('debito','pix','dinheiro','credito'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE finance_expenses DROP CONSTRAINT IF EXISTS finance_expenses_forma_pagamento_check');
        DB::statement("ALTER TABLE finance_expenses ADD CONSTRAINT finance_expenses_forma_pagamento_check CHECK (forma_pagamento IN ('debito','pix','dinheiro'))");
    }
};
