<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Database\Seeder;

class CustomerPurchaseTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates sample sales orders and items so that
     * the "สินค้าที่เคยซื้อ" tab on the customer detail page
     * has data to display.
     */
    public function run(): void
    {
        // เลือกลูกค้าหนึ่งราย (คนแรกในระบบ)
        $customer = Customer::query()->first();

        if (! $customer) {
            $this->command?->error('No customers found. Please run CustomerSeeder first.');

            return;
        }

        // เลือกสินค้า 5 ชิ้นแรกที่เป็น active
        $products = Product::query()
            ->where('status', 'active')
            ->limit(5)
            ->get();

        if ($products->isEmpty()) {
            $this->command?->error('No active products found. Please run ProductSeeder / E2ETestSeeder first.');

            return;
        }

        // สร้าง Sales Order สำหรับลูกค้ารายนี้
        $salesOrder = SalesOrder::create([
            'customer_id' => $customer->id,
            'status' => 'completed',
            'total_amount' => 0,
            'notes' => 'Test purchases for customer detail page (CustomerPurchaseTestSeeder)',
        ]);

        $total = 0;

        foreach ($products as $product) {
            $qty = random_int(1, 3);
            $price = $product->price_tagged ?? 1000;
            $discount = 0;
            $lineTotal = ($price * $qty) - $discount;

            SalesOrderItem::create([
                'sales_order_id' => $salesOrder->id,
                'product_id' => $product->id,
                'qty' => $qty,
                'price' => $price,
                'discount' => $discount,
                'total' => $lineTotal,
            ]);

            $total += $lineTotal;
        }

        // อัปเดตรวมยอด order
        $salesOrder->update([
            'total_amount' => $total,
        ]);

        $this->command?->info(sprintf(
            'Created test purchases for customer: %s (ID: %d), sales_order_id: %d',
            $customer->name,
            $customer->id,
            $salesOrder->id
        ));
    }
}


