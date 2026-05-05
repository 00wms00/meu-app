<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class LimparProdutosOrfaos extends Command
{
    protected $signature = 'produtos:limpar-orfaos';
    protected $description = 'Remove produtos que não estão vinculados a nenhuma nota fiscal';

    public function handle()
    {
        $orfao = Product::whereDoesntHave('invoiceItems')->count();
        $this->info("Produtos órfãos encontrados: {$orfao}");
        
        if ($orfao > 0) {
            if ($this->confirm('Deseja remover todos os produtos órfãos?')) {
                $deletados = Product::whereDoesntHave('invoiceItems')->delete();
                $this->info("✅ {$deletados} produto(s) órfão(s) removido(s)!");
            }
        }
        
        return 0;
    }
}
