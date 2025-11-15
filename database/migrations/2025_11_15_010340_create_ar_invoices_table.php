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
        Schema::create('ar_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                ->constrained('customers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('invoice_no')->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            $table->decimal('grand_total', 12, 2);   // ยอดตามเอกสาร
            $table->decimal('paid_total', 12, 2)->default(0);  // ยอดที่จ่ายแล้ว (ตาม allocation)
            $table->decimal('open_amount', 12, 2)->default(0); // grand_total - paid_total (จะอัปเดตผ่าน Service)

            $table->string('currency', 3)->default('THB');

            $table->enum('status', ['open', 'partial', 'paid', 'cancelled'])
                ->default('open');

            $table->string('reference_type')->nullable(); // เช่น 'sales_order', 'delivery_order'
            $table->unsignedBigInteger('reference_id')->nullable(); // id ของเอกสารต้นทาง

            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index('invoice_date');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ar_invoices');
    }
};
