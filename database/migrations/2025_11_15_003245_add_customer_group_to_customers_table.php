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
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('customer_group', [
                'personal',
                'government',
                'organization',
            ])
            ->default('personal')
            ->after('payment_type')
            ->comment('personal=บุคคลธรรมดา, government=ข้าราชการ/รัฐ, organization=องค์กร/บริษัท');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('customer_group');
        });
    }
};
