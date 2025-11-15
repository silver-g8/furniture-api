<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ar_invoices', function (Blueprint $table) {
            // Add new amount fields
            $table->decimal('subtotal_amount', 15, 2)->default(0)->after('grand_total');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('subtotal_amount');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('discount_amount');

            // Add sales_order_id FK
            $table->foreignId('sales_order_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('sales_orders')
                ->nullOnDelete();

            // Add timestamp fields
            $table->dateTime('issued_at')->nullable()->after('status');
            $table->dateTime('cancelled_at')->nullable()->after('issued_at');
        });

        // Migrate existing data first
        DB::statement("UPDATE ar_invoices SET status = 'issued' WHERE status = 'open'");
        DB::statement("UPDATE ar_invoices SET status = 'partially_paid' WHERE status = 'partial'");

        // Update status enum: use database-agnostic approach
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't support MODIFY COLUMN or ENUM
            // The status column is already a string in SQLite, so we just update the data
            // Laravel will handle enum validation at application level
        } else {
            // For MySQL/MariaDB - modify enum column
            DB::statement("
                ALTER TABLE ar_invoices
                MODIFY COLUMN status ENUM('draft', 'issued', 'partially_paid', 'paid', 'cancelled')
                NOT NULL DEFAULT 'draft'
            ");
        }

        // Set default values for new amount fields based on grand_total
        DB::statement("
            UPDATE ar_invoices
            SET subtotal_amount = grand_total,
                discount_amount = 0,
                tax_amount = 0
            WHERE subtotal_amount = 0
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert status enum
        DB::statement("UPDATE ar_invoices SET status = 'open' WHERE status = 'issued'");
        DB::statement("UPDATE ar_invoices SET status = 'partial' WHERE status = 'partially_paid'");

        $driver = DB::getDriverName();
        
        if ($driver !== 'sqlite') {
            // For MySQL/MariaDB
            DB::statement("
                ALTER TABLE ar_invoices
                MODIFY COLUMN status ENUM('open', 'partial', 'paid', 'cancelled')
                NOT NULL DEFAULT 'open'
            ");
        }

        Schema::table('ar_invoices', function (Blueprint $table) {
            $table->dropForeign(['sales_order_id']);
            $table->dropColumn([
                'subtotal_amount',
                'discount_amount',
                'tax_amount',
                'sales_order_id',
                'issued_at',
                'cancelled_at',
            ]);
        });
    }
};
