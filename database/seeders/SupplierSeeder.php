<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'code' => 'SUP-001',
                'name' => 'Furniture Wholesale Co., Ltd.',
                'contact_name' => 'John Smith',
                'phone' => '02-123-4567',
                'email' => 'contact@furniturewholesale.com',
                'address' => '123 Industrial Road, Bangkok 10100',
                'is_active' => true,
            ],
            [
                'code' => 'SUP-002',
                'name' => 'Wood & Design Supplies',
                'contact_name' => 'Sarah Johnson',
                'phone' => '02-234-5678',
                'email' => 'sales@wooddesign.com',
                'address' => '456 Timber Street, Bangkok 10200',
                'is_active' => true,
            ],
            [
                'code' => 'SUP-003',
                'name' => 'Modern Home Furnishings',
                'contact_name' => 'Michael Chen',
                'phone' => '02-345-6789',
                'email' => 'info@modernhome.com',
                'address' => '789 Design Avenue, Bangkok 10300',
                'is_active' => true,
            ],
            [
                'code' => 'SUP-004',
                'name' => 'Classic Furniture Imports',
                'contact_name' => 'Emily Brown',
                'phone' => '02-456-7890',
                'email' => 'orders@classicimports.com',
                'address' => '321 Import Lane, Bangkok 10400',
                'is_active' => true,
            ],
            [
                'code' => 'SUP-005',
                'name' => 'Old Supplier (Inactive)',
                'contact_name' => 'David Wilson',
                'phone' => '02-567-8901',
                'email' => 'old@supplier.com',
                'address' => '999 Old Street, Bangkok 10500',
                'is_active' => false,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
