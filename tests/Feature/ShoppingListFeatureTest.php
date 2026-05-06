<?php

namespace Tests\Feature;

use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShoppingListFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_consegue_criar_lista_de_compras()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('shopping-lists.store'), [
                'nome' => 'Compras do mês',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('shopping_lists', [
            'nome' => 'Compras do mês',
            'user_id' => $user->id,
        ]);
    }

    public function test_visitante_nao_consegue_criar_lista_de_compras()
    {
        $this->post(route('shopping-lists.store'), [
                'nome' => 'Compras do mês',
            ])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('shopping_lists', 0);
    }
}
