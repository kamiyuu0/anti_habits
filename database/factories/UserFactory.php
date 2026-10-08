<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => 'user'.fake()->unique()->numberBetween(1, 99999999),
            'email' => fake()->unique()->safeEmail(),
            'encrypted_password' => static::$password ??= bcrypt('password'),
        ];
    }

    public function lineLinked(?string $uid = null): static
    {
        return $this->state(fn () => ['provider' => 'line', 'uid' => $uid ?? 'U'.fake()->unique()->md5()]);
    }
}
