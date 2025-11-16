<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ApInvoice;
use App\Models\ApPayment;
use App\Models\ApPaymentAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApPaymentAllocation>
 */
class ApPaymentAllocationFactory extends Factory
{
    protected $model = ApPaymentAllocation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => ApPayment::factory(),
            'invoice_id' => ApInvoice::factory(),
            'allocated_amount' => $this->faker->randomFloat(2, 100, 10000),
        ];
    }
}
