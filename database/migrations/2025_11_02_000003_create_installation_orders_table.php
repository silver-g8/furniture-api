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
        Schema::create('installation_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->onDelete('restrict');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->foreignId('installation_address_id')->nullable()->constrained('customer_addresses')->onDelete('set null');
            $table->text('installation_address_override')->nullable();
            $table->string('installation_contact_name', 255)->nullable();
            $table->string('installation_contact_phone', 20)->nullable();
            $table->enum('status', ['draft', 'scheduled', 'in_progress', 'completed', 'no_show', 'pending_parts'])->default('draft');
            $table->text('deletion_reason')->nullable();
            $table->timestamp('sla_paused_at')->nullable();
            $table->timestamp('sla_resumed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->index('sales_order_id');
            $table->index('customer_id');
            $table->index('deleted_at');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installation_orders');
    }
};
