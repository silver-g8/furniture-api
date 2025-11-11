<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 20 active customers
        Customer::factory()->count(20)->create();

        // Create 5 inactive customers
        Customer::factory()->inactive()->count(5)->create();
    }
}
