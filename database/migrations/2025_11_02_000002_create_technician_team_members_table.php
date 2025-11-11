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
        Schema::create('technician_team_members', function (Blueprint $table) {
            $table->foreignId('team_id')->constrained('technician_teams')->onDelete('cascade');
            $table->foreignId('technician_id')->constrained('users')->onDelete('restrict');
            $table->enum('role', ['lead', 'member'])->default('member');
            $table->dateTime('joined_at');
            $table->dateTime('left_at')->nullable();

            // Composite primary key
            $table->primary(['team_id', 'technician_id']);

            // Indexes
            $table->index('team_id');
            $table->index('technician_id');
            $table->index(['team_id', 'left_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technician_team_members');
    }
};
