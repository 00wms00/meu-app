<?php

namespace App\Providers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Offer;
use App\Models\PriceAlert;
use App\Models\Product;
use App\Models\ShoppingList;
use App\Policies\BudgetPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\OfferPolicy;
use App\Policies\PriceAlertPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ShoppingListPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Mapeamento explícito de Model => Policy.
     *
     * O auto-discovery do Laravel funciona quando Model e Policy seguem
     * a convenção de nomes e estão nos namespaces padrão. O registro
     * explícito aqui garante que funcione independentemente de qualquer
     * customização de namespace ou nome de classe.
     */
    protected $policies = [
        Budget::class       => BudgetPolicy::class,
        Category::class     => CategoryPolicy::class,
        Invoice::class      => InvoicePolicy::class,
        Offer::class        => OfferPolicy::class,
        PriceAlert::class   => PriceAlertPolicy::class,
        Product::class      => ProductPolicy::class,
        ShoppingList::class => ShoppingListPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
