<?php

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
        Schema::table('products', function (Blueprint $table) {
            // products table no longer has a base "price" column, so attach new fields after status
            $table->decimal('price_tagged', 10, 2)->nullable()->after('status')->comment('ราคาตั้งป้าย');
            $table->decimal('price_discounted_tag', 10, 2)->nullable()->after('price_tagged')->comment('ราคาลดป้าย');
            $table->decimal('price_discounted_net', 10, 2)->nullable()->after('price_discounted_tag')->comment('ราคาลดสุทธิ');
            $table->decimal('price_vat', 10, 2)->nullable()->after('price_discounted_net')->comment('ราคา vat');
            $table->decimal('price_vat_credit', 10, 2)->nullable()->after('price_vat')->comment('ราคา vat+credit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'price_tagged',
                'price_discounted_tag',
                'price_discounted_net',
                'price_vat',
                'price_vat_credit',
            ]);
        });
    }
};
