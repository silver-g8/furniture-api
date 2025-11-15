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
            $table->enum('payment_type', ['cash', 'credit'])
                ->default('cash')
                ->comment('ประเภทชำระ: cash=เงินสด, credit=เครดิต')
                ->after('phone');

            $table->decimal('credit_limit', 12, 2)
                ->nullable()
                ->after('payment_type')
                ->comment('วงเงินเครดิตสูงสุด');

            $table->unsignedInteger('credit_term_days')
                ->nullable()
                ->after('credit_limit')
                ->comment('จำนวนวันเครดิต เช่น 30, 45, 60');

            $table->decimal('outstanding_balance', 12, 2)
                ->default(0)
                ->after('credit_term_days')
                ->comment('ยอดค้างชำระล่าสุด (cache)');

            $table->text('credit_note')
                ->nullable()
                ->after('outstanding_balance')
                ->comment('หมายเหตุเกี่ยวกับเครดิต');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'payment_type',
                'credit_limit',
                'credit_term_days',
                'outstanding_balance',
                'credit_note',
            ]);
        });
    }
};
