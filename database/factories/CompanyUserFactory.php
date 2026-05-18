<?php

namespace Database\Factories;

use App\Models\CompanyUser;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class CompanyUserFactory extends Factory
{
    protected $model = CompanyUser::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password123'),
            'status' => 'active',
            'last_login_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
