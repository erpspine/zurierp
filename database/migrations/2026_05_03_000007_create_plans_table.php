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
        if (! Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->string('subtitle')->nullable();
                $table->decimal('monthly_price', 10, 2)->nullable();
                $table->boolean('is_custom_pricing')->default(false);
                $table->unsignedInteger('users_limit')->nullable();
                $table->unsignedInteger('branches_limit')->nullable();
                $table->unsignedInteger('vehicles_limit')->nullable();
                $table->unsignedInteger('bookings_limit')->nullable();
                $table->json('features')->nullable();
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
