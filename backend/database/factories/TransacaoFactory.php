<?php

namespace Database\Factories;

use App\Domain\Entities\Transacao;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransacaoFactory extends Factory
{
    protected $model = Transacao::class;

    public function definition()
    {
        return [
            'account_id' => \App\Domain\Entities\Conta::factory(),
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'type' => $this->faker->randomElement(['deposit', 'withdraw', 'transfer']),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'public_id' => (string) \Illuminate\Support\Str::uuid(),
        ];
    }
}