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
        Schema::create('ap_invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('purchase_id')
                ->nullable()
                ->constrained('purchases')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('invoice_no')->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            $table->decimal('subtotal_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->decimal('paid_total', 12, 2)->default(0);
            $table->decimal('open_amount', 12, 2)->default(0);

            $table->string('currency', 3)->default('THB');

            $table->enum('status', ['draft', 'issued', 'partially_paid', 'paid', 'cancelled'])
                ->default('draft');

            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->text('note')->nullable();

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
            $table->index('invoice_date');
            $table->index('due_date');
            $table->index('purchase_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ap_invoices');
    }
};
