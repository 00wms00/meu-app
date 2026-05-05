<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductGrouperService;
use Illuminate\Console\Command;

class AgruparProdutosExistentes extends Command
{
    protected $signature = 'produtos:agrupar {user_id? : ID do usuário}';
    protected $description = 'Agrupa automaticamente todos os produtos existentes por similaridade';

    public function handle(ProductGrouperService $grouper)
    {
        $userId = $this->argument('user_id');
        
        if ($userId) {
            $produtos = Product::where('user_id', $userId)->orderBy('nome')->get();
            $this->info('Agrupando produtos do usuário ID: ' . $userId);
        } else {
            $produtos = Product::orderBy('user_id')->orderBy('nome')->get();
            $this->info('Agrupando produtos de todos os usuários');
        }
        
        $this->info('Total de produtos encontrados: ' . $produtos->count());
        $this->line('');
        
        $agrupados = 0;
        $canonicos = 0;
        $pulados = 0;
        
        $bar = $this->output->createProgressBar($produtos->count());
        $bar->start();
        
        foreach ($produtos as $produto) {
            // Pular se já está agrupado ou é canônico
            if ($produto->canonical_product_id || $produto->is_canonical) {
                $pulados++;
                $bar->advance();
                continue;
            }
            
            // Buscar canônico similar
            $canonico = $grouper->encontrarCanonico($produto, $produto->user_id);
            
            if ($canonico) {
                // Agrupar ao canônico encontrado
                $grouper->agrupar($produto, $canonico);
                $agrupados++;
                
                if ($this->getOutput()->isVerbose()) {
                    $this->line('');
                    $this->line("  ✅ <comment>{$produto->nome}</comment> → <info>{$canonico->nome}</info>");
                }
            } else {
                // Tornar canônico
                $grouper->tornarCanonico($produto);
                $canonicos++;
                
                if ($this->getOutput()->isVerbose()) {
                    $this->line('');
                    $this->line("  📌 Novo canônico: <info>{$produto->nome}</info>");
                }
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        
        $this->line('');
        $this->line('');
        $this->info('✅ Agrupamento concluído!');
        $this->info('   📊 Total processado: ' . $produtos->count());
        $this->info('   ⏭️ Já agrupados/pulados: ' . $pulados);
        $this->info('   📌 Novos canônicos: ' . $canonicos);
        $this->info('   🔗 Agrupados a canônicos: ' . $agrupados);
        
        return 0;
    }
}
