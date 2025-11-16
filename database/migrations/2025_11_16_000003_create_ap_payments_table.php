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
        Schema::create('ap_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('payment_no')->unique();
            $table->date('payment_date');

            $table->decimal('total_amount', 12, 2);
            $table->string('payment_method', 50)->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('reference_no', 100)->nullable();

            $table->enum('status', ['draft', 'posted', 'cancelled'])
                ->default('draft');

            $table->text('note')->nullable();

            $table->timestamp('posted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'payment_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ap_payments');
    }
};
