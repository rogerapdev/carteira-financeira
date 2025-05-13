<?php

namespace Database\Factories;

use App\Domain\Entities\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UsuarioFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Usuario::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'), // password
            'phone' => $this->faker->phoneNumber(),
            'document' => Str::random(10),
            'status' => 'active',
            'public_id' => Str::uuid(),
            'remember_token' => Str::random(10),
        ];
    }
}