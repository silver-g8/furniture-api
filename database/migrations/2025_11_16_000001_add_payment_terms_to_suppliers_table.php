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
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('payment_terms', 50)->default('Net 30')->after('is_active');
            $table->integer('credit_days')->default(30)->after('payment_terms');
            $table->decimal('credit_limit', 12, 2)->nullable()->after('credit_days');
            $table->string('tax_id', 50)->nullable()->after('credit_limit');
            $table->string('bank_name', 100)->nullable()->after('tax_id');
            $table->string('bank_account_no', 50)->nullable()->after('bank_name');
            $table->string('bank_account_name', 100)->nullable()->after('bank_account_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'payment_terms',
                'credit_days',
                'credit_limit',
                'tax_id',
                'bank_name',
                'bank_account_no',
                'bank_account_name',
            ]);
        });
    }
};
