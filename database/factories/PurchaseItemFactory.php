<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseItem>
 */
class PurchaseItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty = $this->faker->numberBetween(1, 100);
        $price = $this->faker->randomFloat(2, 100, 5000);
        $discount = $this->faker->randomFloat(2, 0, $price * $qty * 0.1);
        $total = ($qty * $price) - $discount;

        return [
            'purchase_id' => Purchase::factory(),
            'product_id' => Product::factory(),
            'qty' => $qty,
            'price' => $price,
            'discount' => $discount,
            'total' => $total,
        ];
    }
}
