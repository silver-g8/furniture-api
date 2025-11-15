<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\Ar\CustomerBalanceService;
use Illuminate\Console\Command;

class RecalculateCustomerBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ar:recalculate-customer-balances {customer_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate outstanding_balance for all customers or a single customer';

    /**
     * Execute the console command.
     */
    public function handle(CustomerBalanceService $balanceService): int
    {
        $customerId = $this->argument('customer_id');

        if ($customerId) {
            $this->info("Recalculating balance for customer #{$customerId}...");
            $balanceService->recalculateForCustomer((int) $customerId);

            $this->info('Done.');
            return self::SUCCESS;
        }

        $this->info('Recalculating balances for all customers...');

        $ids = Customer::query()->pluck('id');

        foreach ($ids as $id) {
            $balanceService->recalculateForCustomer((int) $id);
        }

        $this->info('All customer balances recalculated.');

        return self::SUCCESS;
    }
}

