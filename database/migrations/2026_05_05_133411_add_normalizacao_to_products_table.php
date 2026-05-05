<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Nome normalizado (assinatura do produto)
            if (!Schema::hasColumn('products', 'nome_normalizado')) {
                $table->string('nome_normalizado')->nullable()->index();
            }
            
            // Nome de exibição amigável (definido pelo usuário)
            if (!Schema::hasColumn('products', 'nome_exibicao')) {
                $table->string('nome_exibicao')->nullable();
            }
            
            // Status da normalização
            if (!Schema::hasColumn('products', 'normalizacao_status')) {
                $table->string('normalizacao_status')->default('pendente');
                // pendente, aprovado, revisar
            }
            
            // Componentes da assinatura (para debugging)
            if (!Schema::hasColumn('products', 'assinatura_componentes')) {
                $table->json('assinatura_componentes')->nullable();
            }
            
            // Quem aprovou/revisou
            if (!Schema::hasColumn('products', 'normalizado_por')) {
                $table->foreignId('normalizado_por')->nullable()->constrained('users')->nullOnDelete();
            }
            
            // Data da normalização
            if (!Schema::hasColumn('products', 'normalizado_em')) {
                $table->timestamp('normalizado_em')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'nome_normalizado',
                'nome_exibicao',
                'normalizacao_status',
                'assinatura_componentes',
                'normalizado_por',
                'normalizado_em',
            ]);
        });
    }
};
