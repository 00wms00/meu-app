<?php

namespace Tests\Feature;

use App\Models\PriceAlert;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceAlertFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_consegue_criar_alerta_de_preco()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->post(route('products.alerta.criar', $product), [
                'limite_alerta' => 10,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('price_alerts', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_visitante_nao_consegue_criar_alerta_de_preco()
    {
        $product = Product::factory()->create();

        $this->post(route('products.alerta.criar', $product), [
                'limite_alerta' => 10,
            ])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('price_alerts', 0);
    }
}
