<?php

namespace Database\Factories;

use App\Enum\UserTypeEnum;
use Faker\Generator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Faker\Provider\pt_BR\Person;
use Faker\Provider\pt_BR\Company;
use Illuminate\Container\Container;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $fakerPerson = new Person(Container::getInstance()->make(Generator::class));

        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'identity' => $fakerPerson->cpf(false),
            'user_type' => UserTypeEnum::USER,
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user is store.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function userStore()
    {
        return $this->state(function (array $attributes) {
            $faker = new Company(Container::getInstance()->make(Generator::class));
            return [
                'user_type' => $faker->cnpj(false),
                'user_type' => UserTypeEnum::STORE,
            ];
        });
}
}
