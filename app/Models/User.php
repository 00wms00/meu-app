<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // ── Casts ─────────────────────────────────────────────────────────────────

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ── Relacionamentos ────────────────────────────────────────────────────────

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    public function shoppingLists()
    {
        return $this->hasMany(ShoppingList::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function priceAlerts()
    {
        return $this->hasMany(PriceAlert::class);
    }

    // ── Helpers de domínio ────────────────────────────────────────────────────

    /**
     * Retorna o orçamento do mês/ano informado.
     * Não cria automaticamente — use apenas para leitura.
     */
    public function budgetDo(int $mes, int $ano): ?Budget
    {
        return $this->budgets()
            ->where('mes', $mes)
            ->where('ano', $ano)
            ->first();
    }

    /**
     * Lista ativa de compras do usuário.
     */
    public function listaAtiva(): ?ShoppingList
    {
        return $this->shoppingLists()
            ->where('ativa', true)
            ->latest()
            ->first();
    }

    /**
     * Gastos do mês atual.
     */
    public function gastoMesAtual(): float
    {
        return $this->invoices()
            ->whereMonth('data_emissao', now()->month)
            ->whereYear('data_emissao', now()->year)
            ->sum('valor_pago');
    }
}
