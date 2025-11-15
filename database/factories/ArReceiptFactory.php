<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ArReceipt;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArReceipt>
 */
class ArReceiptFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ArReceipt::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'receipt_no' => 'RCP-' . now()->format('Ymd') . '-' . fake()->unique()->numerify('####'),
            'receipt_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'total_amount' => fake()->randomFloat(2, 100, 20000),
            'payment_method' => fake()->randomElement(['cash', 'transfer', 'credit_card', 'cheque', 'other']),
            'reference' => fake()->optional()->numerify('REF-####'),
            'reference_no' => fake()->optional()->numerify('REF-####'),
            'note' => fake()->optional()->sentence(),
            'status' => 'draft',
            'posted_at' => null,
            'cancelled_at' => null,
        ];
    }

    /**
     * Indicate that the receipt is posted.
     */
    public function posted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'posted',
            'posted_at' => fake()->dateTimeBetween($attributes['receipt_date'], 'now'),
        ]);
    }

    /**
     * Indicate that the receipt is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => fake()->dateTimeBetween($attributes['receipt_date'] ?? 'now', 'now'),
        ]);
    }
}

