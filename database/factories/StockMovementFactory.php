<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Stock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stock_id' => Stock::factory(),
            'type' => $this->faker->randomElement(['in', 'out']),
            'quantity' => $this->faker->numberBetween(1, 100),
            'reference_type' => $this->faker->randomElement(['purchase_order', 'sales_order', 'adjustment', null]),
            'reference_id' => $this->faker->optional()->numberBetween(1, 1000),
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the movement is an IN movement.
     */
    public function in(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'in',
        ]);
    }

    /**
     * Indicate that the movement is an OUT movement.
     */
    public function out(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'out',
        ]);
    }
}
