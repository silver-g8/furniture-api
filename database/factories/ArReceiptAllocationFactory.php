<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ArInvoice;
use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ArReceiptAllocation>
 */
class ArReceiptAllocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ArReceiptAllocation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'receipt_id' => ArReceipt::factory(),
            'invoice_id' => ArInvoice::factory(),
            'allocated_amount' => fake()->randomFloat(2, 100, 10000),
        ];
    }

    /**
     * Allocate to a specific invoice.
     */
    public function forInvoice(ArInvoice $invoice): static
    {
        return $this->state(function (array $attributes) use ($invoice) {
            $maxAmount = min($invoice->open_amount, $attributes['allocated_amount'] ?? 10000);

            return [
                'invoice_id' => $invoice->id,
                'allocated_amount' => fake()->randomFloat(2, 100, $maxAmount),
            ];
        });
    }

    /**
     * Allocate from a specific receipt.
     */
    public function forReceipt(ArReceipt $receipt): static
    {
        return $this->state(function (array $attributes) use ($receipt) {
            $maxAmount = min($receipt->total_amount, $attributes['allocated_amount'] ?? 10000);

            return [
                'receipt_id' => $receipt->id,
                'allocated_amount' => fake()->randomFloat(2, 100, $maxAmount),
            ];
        });
    }
}

