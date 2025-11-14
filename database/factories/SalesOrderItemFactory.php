<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SalesOrderItem>
 */
class SalesOrderItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = SalesOrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty = fake()->numberBetween(1, 10);
        $price = fake()->randomFloat(2, 100, 10000);
        $discount = fake()->randomFloat(2, 0, $price * 0.2);
        $total = ($price * $qty) - $discount;

        return [
            'sales_order_id' => SalesOrder::factory(),
            'product_id' => Product::factory(),
            'qty' => $qty,
            'price' => $price,
            'discount' => $discount,
            'total' => $total,
        ];
    }
}
