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
        'km_atual' => 'integer',
        'ano'      => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function expenses()
    {
        return $this->hasMany(VehicleExpense::class);
    }

    public function fuelEntries()
    {
        return $this->hasMany(FuelEntry::class);
    }

    /**
     * Soma total gasta em abastecimentos
     */
    public function totalCombustivel(): float
    {
        return (float) $this->fuelEntries()->sum('valor');
    }

    /**
     * Soma total gasta em manutenções e outros (VehicleExpense)
     */
    public function totalDespesas(): float
    {
        return (float) $this->expenses()->sum('valor');
    }
}
