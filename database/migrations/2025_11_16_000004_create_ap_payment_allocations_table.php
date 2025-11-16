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
        Schema::create('ap_payment_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_id')
                ->constrained('ap_payments')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('invoice_id')
                ->constrained('ap_invoices')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->decimal('allocated_amount', 12, 2);

            $table->timestamps();

            $table->index('payment_id');
            $table->index('invoice_id');
            $table->unique(['payment_id', 'invoice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ap_payment_allocations');
    }
};
