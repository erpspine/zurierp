<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'legal_name' => $this->faker->company(),
            'company_code' => 'ZT-' . str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 3, '0', STR_PAD_LEFT),
            'registration_number' => $this->faker->unique()->numerify('REG-#####'),
            'tin' => $this->faker->numerify('TIN-#########'),
            'vat_number' => $this->faker->numerify('VAT-#########'),
            'industry' => $this->faker->randomElement(['Tourism', 'Hospitality', 'Transportation', 'Entertainment']),
            'business_type' => $this->faker->randomElement(['Ltd', 'PLC', 'Partnership', 'Sole Proprietor']),
            'incorporation_date' => $this->faker->dateTimeBetween('-10 years', 'now'),
            'country' => $this->faker->country(),
            'region' => $this->faker->state(),
            'city' => $this->faker->city(),
            'address_line_1' => $this->faker->streetAddress(),
            'address_line_2' => $this->faker->optional()->secondaryAddress(),
            'postal_code' => $this->faker->postcode(),
            'phone' => $this->faker->phoneNumber(),
            'alt_phone' => $this->faker->optional()->phoneNumber(),
            'email' => $this->faker->unique()->companyEmail(),
            'website' => $this->faker->optional()->url(),
            'whatsapp' => $this->faker->optional()->phoneNumber(),
            'default_currency' => 'USD',
            'multi_currency_enabled' => false,
            'financial_year_start' => 1,
            'tax_enabled' => false,
            'notify_email' => true,
            'notify_whatsapp' => false,
            'notify_sms' => false,
            'status' => 'active',
            'subscription_status' => 'trial',
        ];
    }
}
