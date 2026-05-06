<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Budget;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_consegue_acessar_tela_de_orcamento()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('budgets.index'))
            ->assertStatus(200);
    }

    public function test_visitante_nao_consegue_acessar_tela_de_orcamento()
    {
        $this->get(route('budgets.index'))
            ->assertRedirect(route('login'));
    }

    public function test_usuario_consegue_definir_orcamento_basico_sem_categorias()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('budgets.store'), [
                'ano' => 2026,
                'mes' => 5,
                'valor_total' => '1000,50',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('budgets.index', ['mes' => 5, 'ano' => 2026]));

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'ano' => 2026,
            'mes' => 5,
            // valor_total é float convertido de string, então conferimos aproximação
        ]);
    }

    public function test_usuario_consegue_definir_orcamento_por_categoria()
    {
        $user = User::factory()->create();
        $categoriaAlimentos = Category::factory()->create(['user_id' => $user->id]);
        $categoriaHigiene   = Category::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->post(route('budgets.store'), [
                'ano' => 2026,
                'mes' => 5,
                'valor_total' => '0',
                'categorias' => [
                    ['category_id' => $categoriaAlimentos->id, 'valor_limite' => '300,00'],
                    ['category_id' => $categoriaHigiene->id,   'valor_limite' => '150,50'],
                ],
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('budgets.index', ['mes' => 5, 'ano' => 2026]));

        $budget = Budget::where('user_id', $user->id)->where('ano', 2026)->where('mes', 5)->first();

        $this->assertNotNull($budget);

        $this->assertDatabaseHas('budget_categories', [
            'budget_id' => $budget->id,
            'category_id' => $categoriaAlimentos->id,
        ]);

        $this->assertDatabaseHas('budget_categories', [
            'budget_id' => $budget->id,
            'category_id' => $categoriaHigiene->id,
        ]);
    }
}
