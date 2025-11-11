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
        Schema::create('installation_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('installation_order_id')->constrained('installation_orders')->onDelete('cascade');
            $table->enum('category', ['before', 'during', 'after', 'issue']);
            $table->string('file_path', 500);
            $table->string('thumbnail_path', 500);
            $table->text('caption')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('restrict');
            $table->integer('display_order')->default(0);
            $table->timestamp('uploaded_at');
            $table->timestamps();

            // Indexes
            $table->index('installation_order_id');
            $table->index('category');
            $table->index(['installation_order_id', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installation_photos');
    }
};
