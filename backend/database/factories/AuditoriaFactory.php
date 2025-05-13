<?php

namespace Database\Factories;

use App\Domain\Entities\Auditoria;
use App\Domain\Entities\Usuario;
use App\Domain\Entities\Conta;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditoriaFactory extends Factory
{
    protected $model = Auditoria::class;

    public function definition(): array
    {
        return [
            'acao' => $this->faker->randomElement(['criar_conta', 'login', 'transferencia', 'deposito', 'consulta']),
            'recurso' => $this->faker->randomElement(['conta', 'autenticacao', 'transacao']),
            'usuario_id' => Usuario::factory(),
            // 'conta_id' => Conta::factory(),
            'request_id' => $this->faker->uuid(),
            'ip' => $this->faker->ipv4(),
            'method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'url' => $this->faker->url(),
            'user_agent' => $this->faker->userAgent(),
            'detalhes' => ['mensagem' => $this->faker->sentence()],
            'nivel' => $this->faker->randomElement(['info', 'warning', 'error'])
        ];
    }
}