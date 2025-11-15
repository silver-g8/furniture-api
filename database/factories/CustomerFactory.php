<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'CUST-'.fake()->unique()->numerify('######'),
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
            'payment_type' => 'cash',
            'credit_limit' => null,
            'credit_term_days' => null,
            'outstanding_balance' => 0,
            'credit_note' => null,
        ];
    }

    /**
     * Indicate that the customer is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the customer is on credit payment.
     */
    public function credit(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => 'credit',
            'credit_limit' => fake()->randomFloat(2, 10000, 100000),
            'credit_term_days' => fake()->randomElement([30, 45, 60]),
            'credit_note' => fake()->optional()->sentence(),
        ]);
    }
}
