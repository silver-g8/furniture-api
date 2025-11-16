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
        Schema::table('products', static function (Blueprint $table): void {
            $table->text('description')
                ->nullable()
                ->after('name');

            // products no longer have a base price column; attach cost after status instead
            $table->decimal('cost', 12, 2)
                ->nullable()
                ->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', static function (Blueprint $table): void {
            $table->dropColumn(['description', 'cost']);
        });
    }
};
