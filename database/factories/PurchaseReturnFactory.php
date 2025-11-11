<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseReturn>
 */
class PurchaseReturnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_id' => Purchase::factory(),
            'warehouse_id' => Warehouse::factory(),
            'returned_at' => null,
            'reason' => $this->faker->optional()->sentence,
            'status' => 'draft',
            'total' => 0,
            'notes' => $this->faker->optional()->paragraph,
        ];
    }

    /**
     * Indicate that the purchase return is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'returned_at' => null,
        ]);
    }

    /**
     * Indicate that the purchase return is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'returned_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }
}
