<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'ano', 'mes', 'valor_total'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function budgetCategories()
    {
        return $this->hasMany(BudgetCategory::class);
    }
}
