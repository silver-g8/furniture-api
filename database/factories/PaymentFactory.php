<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'amount' => fake()->randomFloat(2, 100, 50000),
            'method' => fake()->randomElement(['cash', 'credit_card', 'bank_transfer', 'check']),
            'paid_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'ref_no' => fake()->optional()->bothify('PAY-####-????'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
