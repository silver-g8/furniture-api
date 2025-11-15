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
        Schema::table('ar_receipts', function (Blueprint $table) {
            // Add status enum
            $table->enum('status', ['draft', 'posted', 'cancelled'])
                ->default('draft')
                ->after('total_amount');

            // Add timestamp fields
            $table->dateTime('posted_at')->nullable()->after('status');
            $table->dateTime('cancelled_at')->nullable()->after('posted_at');

            // Add reference_no (alias for reference, keeping reference for backward compatibility)
            $table->string('reference_no')->nullable()->after('reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ar_receipts', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'posted_at',
                'cancelled_at',
                'reference_no',
            ]);
        });
    }
};
