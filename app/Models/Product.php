<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

protected $fillable = [
    'user_id', 'nome', 'unidade_padrao', 'foto', 'categoria', 'category_id',
    'canonical_product_id', 'nome_normalizado', 'keywords', 'is_canonical',
];

    protected $casts = [
        'keywords' => 'array',
        'is_canonical' => 'boolean',
    ];

    // Categorias padrão (fallback)
    const CATEGORIAS_PADRAO = [
        'hortifruti' => '🥬 Hortifrúti',
        'acougue' => '🥩 Açougue e Peixaria',
        'laticinios' => '🧀 Laticínios e Frios',
        'mercearia' => '🍚 Mercearia Seca',
        'padaria' => '🍞 Padaria',
        'bebidas' => '🥤 Bebidas',
        'higiene' => '🧴 Higiene Pessoal',
        'limpeza' => '🧹 Limpeza',
        'outros' => '📦 Outros',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function canonicalProduct()
    {
        return $this->belongsTo(Product::class, 'canonical_product_id');
    }

    public function groupedProducts()
    {
        return $this->hasMany(Product::class, 'canonical_product_id');
    }

    public function scopeCanonical($query)
    {
        return $query->where('is_canonical', true)->orWhereNull('canonical_product_id');
    }

    public function scopeOrfaos($query)
    {
        return $query->whereNull('canonical_product_id')->where('is_canonical', false);
    }

    public function getCategoriaNomeAttribute()
    {
        if ($this->category) {
            return ($this->category->emoji ? $this->category->emoji . ' ' : '') . $this->category->nome;
        }
        return self::CATEGORIAS_PADRAO[$this->categoria] ?? '📦 Sem categoria';
    }

    public function getCategoriaEmojiAttribute()
    {
        if ($this->category && $this->category->emoji) {
            return $this->category->emoji;
        }
        $emojis = ['hortifruti'=>'🥬','acougue'=>'🥩','laticinios'=>'🧀','mercearia'=>'🍚','padaria'=>'🍞','bebidas'=>'🥤','higiene'=>'🧴','limpeza'=>'🧹','outros'=>'📦'];
        return $emojis[$this->categoria] ?? '📦';
    }

    public function getCategoriaCorAttribute()
    {
        if ($this->category) {
            return $this->category->cor;
        }
        return '#6b7280';
    }

    // Acessor para URL da foto
public function getFotoUrlAttribute()
{
    if ($this->foto) {
        return asset('storage/' . $this->foto);
    }
    return null;
}
}
