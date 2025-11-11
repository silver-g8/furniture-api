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
        Schema::create('installation_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installation_order_id')->constrained('installation_orders')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('technician_teams')->onDelete('restrict');
            $table->dateTime('scheduled_at');
            $table->dateTime('completed_at')->nullable();
            $table->integer('estimated_duration_minutes')->default(120);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('installation_order_id');
            $table->index('team_id');
            $table->index('scheduled_at');
            $table->index(['team_id', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installation_schedules');
    }
};
