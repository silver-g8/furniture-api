<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 1000, 50000);
        $discount = $this->faker->randomFloat(2, 0, $subtotal * 0.1);
        $tax = $subtotal * 0.07; // 7% tax
        $grandTotal = $subtotal - $discount + $tax;

        return [
            'supplier_id' => Supplier::factory(),
            'status' => $this->faker->randomElement(['draft', 'approved']),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'grand_total' => $grandTotal,
            'notes' => $this->faker->optional()->sentence,
        ];
    }

    /**
     * Indicate that the purchase is in draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the purchase is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }
}
