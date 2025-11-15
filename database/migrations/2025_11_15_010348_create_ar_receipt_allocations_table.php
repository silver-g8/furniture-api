<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ar_receipt_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('receipt_id')
                ->constrained('ar_receipts')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('invoice_id')
                ->constrained('ar_invoices')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->decimal('allocated_amount', 12, 2); // ยอดที่ใช้ตัด invoice ใบนี้

            $table->timestamps();

            $table->unique(['receipt_id', 'invoice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ar_receipt_allocations');
    }
};
