<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;

class InserirCategoriasPadrao extends Command
{
    protected $signature = 'categorias:padrao {user_id? : ID do usuário}';
    protected $description = 'Insere as categorias padrão no banco de dados';

    protected $categorias = [
        ['nome' => 'Hortifrúti', 'emoji' => '🥬', 'cor' => '#22c55e', 'descricao' => 'Frutas, legumes e verduras', 'ordem' => 1],
        ['nome' => 'Açougue e Peixaria', 'emoji' => '🥩', 'cor' => '#ef4444', 'descricao' => 'Carnes bovinas, frango, suínos e peixes', 'ordem' => 2],
        ['nome' => 'Laticínios e Frios', 'emoji' => '🧀', 'cor' => '#f59e0b', 'descricao' => 'Leite, queijos, iogurtes, manteiga e presuntos', 'ordem' => 3],
        ['nome' => 'Mercearia Seca', 'emoji' => '🍚', 'cor' => '#8b5cf6', 'descricao' => 'Arroz, feijão, massas, farinhas, óleos e grãos', 'ordem' => 4],
        ['nome' => 'Padaria', 'emoji' => '🍞', 'cor' => '#d97706', 'descricao' => 'Pães, bolos e biscoitos frescos', 'ordem' => 5],
        ['nome' => 'Bebidas', 'emoji' => '🥤', 'cor' => '#06b6d4', 'descricao' => 'Sucos, refrigerantes, águas e bebidas alcoólicas', 'ordem' => 6],
        ['nome' => 'Higiene Pessoal', 'emoji' => '🧴', 'cor' => '#ec4899', 'descricao' => 'Shampoo, sabonete, creme dental e desodorante', 'ordem' => 7],
        ['nome' => 'Limpeza', 'emoji' => '🧹', 'cor' => '#6366f1', 'descricao' => 'Detergente, sabão em pó, amaciante e desinfetantes', 'ordem' => 8],
        ['nome' => 'Outros', 'emoji' => '📦', 'cor' => '#6b7280', 'descricao' => 'Produtos não categorizados', 'ordem' => 9],
    ];

    public function handle()
    {
        $userId = $this->argument('user_id');
        
        if ($userId) {
            $this->inserirParaUsuario((int) $userId);
        } else {
            // Inserir para todos os usuários
            $users = \App\Models\User::all();
            foreach ($users as $user) {
                $this->inserirParaUsuario($user->id);
            }
        }

        $this->info('✅ Categorias padrão inseridas com sucesso!');
        return 0;
    }

    private function inserirParaUsuario(int $userId)
    {
        $this->info("Inserindo categorias para usuário ID: {$userId}");
        
        foreach ($this->categorias as $cat) {
            Category::firstOrCreate(
                [
                    'user_id' => $userId,
                    'nome' => $cat['nome'],
                ],
                [
                    'emoji' => $cat['emoji'],
                    'cor' => $cat['cor'],
                    'descricao' => $cat['descricao'],
                    'ordem' => $cat['ordem'],
                ]
            );
        }
        
        $this->info("  → " . Category::where('user_id', $userId)->count() . " categorias");
    }
}
