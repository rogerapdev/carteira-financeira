<?php

namespace Database\Factories;

use App\Domain\Entities\Conta;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContaFactory extends Factory
{
    protected $model = Conta::class;

    public function definition()
    {
        return [
            'user_id' => \App\Domain\Entities\Usuario::factory(),
            'public_id' => (string) \Illuminate\Support\Str::uuid(),
            'balance' => $this->faker->randomFloat(2, 0, 10000),
            'status' => $this->faker->randomElement(['active', 'inactive'])
        ];
    }
}