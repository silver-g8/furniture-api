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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship to auditable documents
            $table->string('auditable_type')->index();
            $table->unsignedBigInteger('auditable_id')->index();

            // Action performed
            $table->enum('action', ['created', 'updated', 'approved', 'cancelled', 'deleted'])->index();

            // Actor information
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Request context
            $table->string('ip', 45)->nullable(); // IPv6 support
            $table->text('user_agent')->nullable();

            // Data snapshots
            $table->json('before')->nullable();
            $table->json('after')->nullable();

            // Additional metadata
            $table->json('meta')->nullable();

            $table->timestamps();

            // Composite index for efficient queries
            $table->index(['auditable_type', 'auditable_id', 'created_at'], 'audit_composite_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
