<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\InstallationStatus;
use App\Models\Customer;
use App\Models\InstallationOrder;
use App\Models\SalesOrder;
use Illuminate\Database\Seeder;

class InstallationOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create customers
        $customers = Customer::limit(10)->get();
        if ($customers->isEmpty()) {
            $customers = Customer::factory()->count(10)->create();
        }

        // Create sales orders if they don't exist
        $salesOrders = SalesOrder::limit(15)->get();
        if ($salesOrders->count() < 15) {
            foreach ($customers as $customer) {
                SalesOrder::factory()->create([
                    'customer_id' => $customer->id,
                    'status' => fake()->randomElement(['paid', 'completed']),
                ]);
            }
            $salesOrders = SalesOrder::whereIn('status', ['paid', 'completed'])->limit(15)->get();
        }

        // Create installation orders in various states
        foreach ($salesOrders as $index => $salesOrder) {
            $status = match ($index % 6) {
                0 => InstallationStatus::Draft,
                1 => InstallationStatus::Scheduled,
                2 => InstallationStatus::InProgress,
                3 => InstallationStatus::Completed,
                4 => InstallationStatus::NoShow,
                5 => InstallationStatus::PendingParts,
                default => InstallationStatus::Draft,
            };

            InstallationOrder::create([
                'sales_order_id' => $salesOrder->id,
                'customer_id' => $salesOrder->customer_id,
                'installation_address_id' => null,
                'installation_address_override' => fake()->address(),
                'installation_contact_name' => fake()->name(),
                'installation_contact_phone' => fake()->phoneNumber(),
                'status' => $status,
                'notes' => fake()->optional()->sentence(),
                'sla_paused_at' => $status->shouldPauseSla() ? now()->subDays(rand(1, 5)) : null,
            ]);
        }
    }
}
