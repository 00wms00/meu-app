<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceItemObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_produto_orfao_e_removido_apos_deletar_unico_item(): void
    {
        $user    = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['user_id' => $user->id]);
        $item    = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
        ]);

        $item->delete();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_produto_com_outros_itens_nao_e_removido(): void
    {
        $user     = User::factory()->create();
        $invoice1 = Invoice::factory()->create(['user_id' => $user->id]);
        $invoice2 = Invoice::factory()->create(['user_id' => $user->id]);
        $product  = Product::factory()->create(['user_id' => $user->id]);

        $item1 = InvoiceItem::factory()->create(['invoice_id' => $invoice1->id, 'product_id' => $product->id]);
        $item2 = InvoiceItem::factory()->create(['invoice_id' => $invoice2->id, 'product_id' => $product->id]);

        $item1->delete();   // exclui apenas um dos dois itens

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
