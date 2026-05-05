<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShoppingListItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shopping_list_id', 'product_id', 'nome', 'quantidade',
        'unidade', 'preco_estimado', 'comprado', 'ordem', 'observacao',
    ];

    protected $casts = [
        'quantidade' => 'float',
        'preco_estimado' => 'float',
        'comprado' => 'boolean',
    ];

    public function shoppingList()
    {
        return $this->belongsTo(ShoppingList::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
