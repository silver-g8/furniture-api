<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SalesReturn>
 */
class SalesReturnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'warehouse_id' => Warehouse::factory(),
            'returned_at' => null,
            'reason' => $this->faker->optional()->sentence,
            'status' => 'draft',
            'total' => 0,
            'notes' => $this->faker->optional()->paragraph,
        ];
    }

    /**
     * Indicate that the sales return is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'returned_at' => null,
        ]);
    }

    /**
     * Indicate that the sales return is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'returned_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }
}
