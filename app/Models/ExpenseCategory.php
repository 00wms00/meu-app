<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $fillable = ['user_id', 'nome', 'cor', 'emoji', 'ordem'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeDoUsuario($query, int $userId)
    {
        return $query->where('user_id', $userId)->orderBy('ordem')->orderBy('nome');
    }
}
