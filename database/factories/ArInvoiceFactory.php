<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ArInvoice;
use App\Models\Customer;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArInvoice>
 */
class ArInvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ArInvoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 1000, 50000);
        $discount = fake()->randomFloat(2, 0, $subtotal * 0.1);
        $tax = fake()->randomFloat(2, 0, $subtotal * 0.07);
        $grandTotal = $subtotal - $discount + $tax;

        return [
            'customer_id' => Customer::factory(),
            'sales_order_id' => null,
            'invoice_no' => 'INV-' . now()->format('Ymd') . '-' . fake()->unique()->numerify('####'),
            'invoice_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+60 days'),
            'currency' => 'THB',
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'grand_total' => $grandTotal,
            'paid_total' => 0,
            'open_amount' => $grandTotal,
            'status' => 'draft',
            'reference_type' => null,
            'reference_id' => null,
            'note' => fake()->optional()->sentence(),
            'issued_at' => null,
            'cancelled_at' => null,
        ];
    }

    /**
     * Indicate that the invoice is issued.
     */
    public function issued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'issued',
            'issued_at' => fake()->dateTimeBetween($attributes['invoice_date'], 'now'),
        ]);
    }

    /**
     * Indicate that the invoice is partially paid.
     */
    public function partiallyPaid(): static
    {
        return $this->state(function (array $attributes) {
            $paid = fake()->randomFloat(2, $attributes['grand_total'] * 0.1, $attributes['grand_total'] * 0.9);
            $open = $attributes['grand_total'] - $paid;

            return [
                'status' => 'partially_paid',
                'paid_total' => $paid,
                'open_amount' => $open,
                'issued_at' => fake()->dateTimeBetween($attributes['invoice_date'], 'now'),
            ];
        });
    }

    /**
     * Indicate that the invoice is paid.
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'paid',
                'paid_total' => $attributes['grand_total'],
                'open_amount' => 0,
                'issued_at' => fake()->dateTimeBetween($attributes['invoice_date'], 'now'),
            ];
        });
    }

    /**
     * Indicate that the invoice is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => fake()->dateTimeBetween($attributes['invoice_date'] ?? 'now', 'now'),
        ]);
    }

    /**
     * Indicate that the invoice is overdue.
     */
    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            $dueDate = fake()->dateTimeBetween('-30 days', '-1 day');

            return [
                'status' => 'issued',
                'due_date' => $dueDate,
                'open_amount' => fake()->randomFloat(2, 100, $attributes['grand_total']),
                'issued_at' => fake()->dateTimeBetween($dueDate->modify('-30 days'), $dueDate),
            ];
        });
    }

    /**
     * Link invoice to a sales order.
     */
    public function fromSalesOrder(SalesOrder $salesOrder): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $salesOrder->customer_id,
            'sales_order_id' => $salesOrder->id,
            'reference_type' => 'sales_order',
            'reference_id' => $salesOrder->id,
        ]);
    }
}

