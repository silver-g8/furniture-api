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
        Schema::create('ar_receipts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('receipt_no')->unique();
            $table->date('receipt_date');

            $table->decimal('total_amount', 12, 2); // ยอดที่รับมาในใบเสร็จ
            $table->string('payment_method')->nullable(); // cash, transfer, cheque, etc.
            $table->string('reference')->nullable(); // เลขที่เช็ค, เลข transaction

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['customer_id', 'receipt_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ar_receipts');
    }
};
