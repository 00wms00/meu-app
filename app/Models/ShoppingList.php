<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShoppingList extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'nome', 'ativa', 'data_compra', 'valor_total'];

    protected $casts = [
        'ativa' => 'boolean',
        'data_compra' => 'date',
        'valor_total' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(ShoppingListItem::class)->orderBy('ordem');
    }

    public function itemsComprados()
    {
        return $this->hasMany(ShoppingListItem::class)->where('comprado', true);
    }

    public function itemsPendentes()
    {
        return $this->hasMany(ShoppingListItem::class)->where('comprado', false);
    }
}
