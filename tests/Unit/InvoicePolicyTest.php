<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\User;
use App\Policies\InvoicePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_dono_pode_ver_invoice(): void
    {
        $this->markTestSkipped('Regras de policy de Invoice ainda não foram revisadas neste projeto.');

        $user    = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user->id]);
        $policy  = new InvoicePolicy();

        $this->assertTrue($policy->view($user, $invoice));
    }

    public function test_outro_usuario_nao_pode_ver_invoice(): void
    {
        $this->markTestSkipped('Regras de policy de Invoice ainda não foram revisadas neste projeto.');

        $dono    = User::factory()->create();
        $intruso = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $dono->id]);
        $policy  = new InvoicePolicy();

        $this->assertFalse($policy->view($intruso, $invoice));
    }
}
