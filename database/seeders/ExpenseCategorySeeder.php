<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /** Categorias padrão criadas para cada usuário existente. */
    private const DEFAULTS = [
        ['nome' => 'Moradia',      'cor' => '#ef4444', 'emoji' => '🏠', 'ordem' => 1],
        ['nome' => 'Alimentação',  'cor' => '#f97316', 'emoji' => '🍽️', 'ordem' => 2],
        ['nome' => 'Transporte',   'cor' => '#eab308', 'emoji' => '🚌', 'ordem' => 3],
        ['nome' => 'Saúde',        'cor' => '#22c55e', 'emoji' => '💊', 'ordem' => 4],
        ['nome' => 'Educação',     'cor' => '#3b82f6', 'emoji' => '📚', 'ordem' => 5],
        ['nome' => 'Lazer',        'cor' => '#a855f7', 'emoji' => '🎮', 'ordem' => 6],
        ['nome' => 'Roupas',       'cor' => '#ec4899', 'emoji' => '👗', 'ordem' => 7],
        ['nome' => 'Assinaturas',  'cor' => '#6366f1', 'emoji' => '📺', 'ordem' => 8],
        ['nome' => 'Carro',        'cor' => '#14b8a6', 'emoji' => '🚗', 'ordem' => 9],
        ['nome' => 'Outros',       'cor' => '#6b7280', 'emoji' => '📦', 'ordem' => 10],
    ];

    public function run(): void
    {
        foreach (User::all() as $user) {
            foreach (self::DEFAULTS as $cat) {
                ExpenseCategory::firstOrCreate(
                    ['user_id' => $user->id, 'nome' => $cat['nome']],
                    ['cor' => $cat['cor'], 'emoji' => $cat['emoji'], 'ordem' => $cat['ordem']]
                );
            }
        }
    }
}
