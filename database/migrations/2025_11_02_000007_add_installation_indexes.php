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
        // Additional composite indexes for performance optimization
        // These support common dashboard and reporting queries

        Schema::table('installation_schedules', function (Blueprint $table) {
            $table->index(['scheduled_at', 'team_id'], 'idx_schedules_date_team');
        });

        Schema::table('installation_photos', function (Blueprint $table) {
            $table->index(['category', 'installation_order_id'], 'idx_photos_category_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installation_schedules', function (Blueprint $table) {
            $table->dropIndex('idx_schedules_date_team');
        });

        Schema::table('installation_photos', function (Blueprint $table) {
            $table->dropIndex('idx_photos_category_order');
        });
    }
};
