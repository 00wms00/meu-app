<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_consegue_acessar_tela_de_importacao()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('import.create'))
            ->assertStatus(200);
    }

    public function test_visitante_nao_consegue_acessar_tela_de_importacao()
    {
        $this->get(route('import.create'))
            ->assertRedirect(route('login'));
    }
}
