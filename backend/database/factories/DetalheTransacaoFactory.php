<?php

namespace Database\Factories;

use App\Domain\Entities\DetalheTransacao;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetalheTransacaoFactory extends Factory
{
    protected $model = DetalheTransacao::class;

    public function definition()
    {
        return [
            'transaction_id' => \App\Domain\Entities\Transacao::factory(),
            'from_account_id' => \App\Domain\Entities\Conta::factory(),
            'to_account_id' => \App\Domain\Entities\Conta::factory(),
            'metadata' => [
                'description' => $this->faker->sentence(),
                'transaction_date' => $this->faker->dateTimeThisMonth()->format('Y-m-d H:i:s'),
                'type' => $this->faker->randomElement(['deposit', 'withdraw', 'transfer'])
            ],
            'public_id' => (string) \Illuminate\Support\Str::uuid(),
        ];
    }
}