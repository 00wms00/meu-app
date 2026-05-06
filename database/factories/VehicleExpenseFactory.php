<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleExpense>
 */
class VehicleExpenseFactory extends Factory
{
    protected $model = VehicleExpense::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vehicle_id' => Vehicle::factory(),
            'data' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'tipo' => $this->faker->randomElement(['combustivel', 'manutencao', 'seguro', 'impostos', 'pedagio', 'outros']),
            'valor' => $this->faker->randomFloat(2, 10, 800),
            'descricao' => $this->faker->sentence(3),
            'invoice_item_id' => null,
        ];
    }
}
