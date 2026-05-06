<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nome' => $this->faker->words(3, true),
            'unidade_padrao' => 'UN',
            'categoria' => 'outros',
            'category_id' => null,
            'canonical_product_id' => null,
            'nome_normalizado' => null,
            'nome_exibicao' => null,
            'normalizacao_status' => null,
            'assinatura_componentes' => null,
            'normalizado_por' => null,
            'normalizado_em' => null,
            'keywords' => [],
            'is_canonical' => true,
        ];
    }
}
