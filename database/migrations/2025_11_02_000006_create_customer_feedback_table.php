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
        Schema::create('customer_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installation_order_id')->unique()->constrained('installation_orders')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict');
            $table->tinyInteger('overall_rating')->unsigned();
            $table->tinyInteger('technician_rating')->unsigned();
            $table->tinyInteger('timeliness_rating')->unsigned();
            $table->tinyInteger('quality_rating')->unsigned();
            $table->text('comments')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();

            // Indexes
            $table->index('customer_id');
            $table->index('overall_rating');
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_feedback');
    }
};
