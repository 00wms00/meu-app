<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;


class InvoicePolicyTest extends TestCase
{
    public function test_dono_pode_ver_invoice(): void
    {
        $user    = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user->id]);
        $policy  = new InvoicePolicy();

        $this->assertTrue($policy->view($user, $invoice));
    }

    public function test_outro_usuario_nao_pode_ver_invoice(): void
    {
        $dono    = User::factory()->create();
        $intruso = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $dono->id]);
        $policy  = new InvoicePolicy();

        $this->assertFalse($policy->view($intruso, $invoice));
    }
}
