<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ApInvoice;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApInvoice>
 */
class ApInvoiceFactory extends Factory
{
    protected $model = ApInvoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $invoiceDate = $this->faker->dateTimeBetween('-3 months', 'now');
        $dueDate = (clone $invoiceDate)->modify('+30 days');

        $subtotal = $this->faker->randomFloat(2, 1000, 50000);
        $discount = $this->faker->randomFloat(2, 0, $subtotal * 0.1);
        $tax = $this->faker->randomFloat(2, 0, ($subtotal - $discount) * 0.07);
        $grandTotal = $subtotal - $discount + $tax;

        return [
            'supplier_id' => Supplier::factory(),
            'purchase_id' => $this->faker->boolean(70) ? Purchase::factory() : null,
            'invoice_no' => 'API-' . $this->faker->unique()->numerify('########'),
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'grand_total' => $grandTotal,
            'paid_total' => 0,
            'open_amount' => $grandTotal,
            'currency' => 'THB',
            'status' => 'draft',
            'reference_type' => null,
            'reference_id' => null,
            'note' => $this->faker->optional()->sentence(),
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
            'invoice_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'due_date' => $this->faker->dateTimeBetween('+1 day', '+60 days'),
            'issued_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Indicate that the invoice is partially paid.
     */
    public function partiallyPaid(): static
    {
        return $this->state(function (array $attributes) {
            $paidAmount = $this->faker->randomFloat(2, 100, $attributes['grand_total'] * 0.8);
            return [
                'status' => 'partially_paid',
                'issued_at' => $this->faker->dateTimeBetween($attributes['invoice_date'], 'now'),
                'paid_total' => $paidAmount,
                'open_amount' => $attributes['grand_total'] - $paidAmount,
            ];
        });
    }

    /**
     * Indicate that the invoice is fully paid.
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'paid',
                'issued_at' => $this->faker->dateTimeBetween($attributes['invoice_date'], 'now'),
                'paid_total' => $attributes['grand_total'],
                'open_amount' => 0,
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
            'cancelled_at' => $this->faker->dateTimeBetween($attributes['invoice_date'], 'now'),
        ]);
    }

    /**
     * Indicate that the invoice is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'issued',
            'invoice_date' => $this->faker->dateTimeBetween('-90 days', '-60 days'),
            'due_date' => $this->faker->dateTimeBetween('-50 days', '-10 days'),
            'issued_at' => $this->faker->dateTimeBetween('-90 days', '-60 days'),
        ]);
    }
}
