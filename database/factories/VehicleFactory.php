<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'apelido' => $this->faker->word(),
            'marca' => $this->faker->randomElement(['VW', 'Fiat', 'Chevrolet', 'Ford', 'Toyota', 'Honda']),
            'modelo' => $this->faker->word(),
            'ano' => $this->faker->numberBetween(1995, (int) now()->year),
            'placa' => strtoupper($this->faker->bothify('???-#?#?')),
            'tipo_combustivel' => $this->faker->randomElement(['Gasolina', 'Etanol', 'Diesel', 'GNV', 'Flex']),
            'km_atual' => $this->faker->numberBetween(0, 250000),
        ];
    }
}
