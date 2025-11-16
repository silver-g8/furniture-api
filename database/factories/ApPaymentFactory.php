<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ApPayment;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApPayment>
 */
class ApPaymentFactory extends Factory
{
    protected $model = ApPayment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paymentMethods = ['cash', 'transfer', 'cheque', 'credit_card'];

        return [
            'supplier_id' => Supplier::factory(),
            'payment_no' => 'APP-' . $this->faker->unique()->numerify('########'),
            'payment_date' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'total_amount' => $this->faker->randomFloat(2, 1000, 50000),
            'payment_method' => $this->faker->randomElement($paymentMethods),
            'reference' => $this->faker->optional()->word(),
            'reference_no' => $this->faker->optional()->numerify('REF-########'),
            'status' => 'draft',
            'note' => $this->faker->optional()->sentence(),
            'posted_at' => null,
            'cancelled_at' => null,
        ];
    }

    /**
     * Indicate that the payment is posted.
     */
    public function posted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'posted',
            'posted_at' => $this->faker->dateTimeBetween($attributes['payment_date'], 'now'),
        ]);
    }

    /**
     * Indicate that the payment is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => $this->faker->dateTimeBetween($attributes['payment_date'], 'now'),
        ]);
    }

    /**
     * Indicate the payment method is cash.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'cash',
        ]);
    }

    /**
     * Indicate the payment method is transfer.
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'transfer',
            'reference' => 'Bank Transfer',
            'reference_no' => 'TRF-' . $this->faker->numerify('##########'),
        ]);
    }

    /**
     * Indicate the payment method is cheque.
     */
    public function cheque(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'cheque',
            'reference' => 'Cheque',
            'reference_no' => 'CHQ-' . $this->faker->numerify('######'),
        ]);
    }
}
