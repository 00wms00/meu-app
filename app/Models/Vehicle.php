<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'apelido',
        'marca',
        'modelo',
        'ano',
        'placa',
        'tipo_combustivel',
        'km_atual',
    ];

    protected $casts = [
        'ano' => 'integer',
        'km_atual' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function expenses()
    {
        return $this->hasMany(VehicleExpense::class);
    }
}
