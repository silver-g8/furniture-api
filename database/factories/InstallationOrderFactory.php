<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InstallationStatus;
use App\Models\Customer;
use App\Models\InstallationOrder;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InstallationOrder>
 */
class InstallationOrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = InstallationOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sales_order_id' => SalesOrder::factory()->paid(),
            'customer_id' => Customer::factory(),
            'installation_address_id' => null,
            'installation_address_override' => fake()->address(),
            'installation_contact_name' => fake()->name(),
            'installation_contact_phone' => fake()->phoneNumber(),
            'status' => InstallationStatus::Draft,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the installation is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InstallationStatus::Scheduled,
        ]);
    }

    /**
     * Indicate that the installation is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InstallationStatus::InProgress,
        ]);
    }

    /**
     * Indicate that the installation is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InstallationStatus::Completed,
        ]);
    }

    /**
     * Indicate that the customer was a no-show.
     */
    public function noShow(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InstallationStatus::NoShow,
            'sla_paused_at' => now()->subDays(rand(1, 5)),
        ]);
    }

    /**
     * Indicate that parts are pending.
     */
    public function pendingParts(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InstallationStatus::PendingParts,
            'sla_paused_at' => now()->subDays(rand(1, 3)),
        ]);
    }
}
