<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nome',
        'emoji',
        'cor',
        'descricao',
        'ordem',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // Scope ordenado
    public function scopeOrdenado($query)
    {
        return $query->orderBy('ordem')->orderBy('nome');
    }
}
