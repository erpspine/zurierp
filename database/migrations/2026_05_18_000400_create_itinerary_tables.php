<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itineraries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedTinyInteger('duration_days')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('company_users')->onDelete('restrict');
            $table->index(['company_id', 'created_at']);
        });

        Schema::create('itinerary_days', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('itinerary_id');
            $table->unsignedTinyInteger('day_number');
            $table->string('title')->nullable();
            $table->date('date')->nullable();
            $table->string('day_highlights')->nullable();
            $table->text('detailed_description')->nullable();
            $table->string('overnight_at')->nullable();
            $table->timestamps();

            $table->foreign('itinerary_id')->references('id')->on('itineraries')->onDelete('cascade');
            $table->index(['itinerary_id', 'day_number']);
        });

        Schema::create('itinerary_accommodations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('itinerary_day_id');
            $table->string('name')->nullable();
            $table->string('type')->nullable();
            $table->string('room_preference')->nullable();
            $table->date('check_in_date')->nullable();
            $table->date('check_out_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('itinerary_day_id')->references('id')->on('itinerary_days')->onDelete('cascade');
        });

        Schema::create('itinerary_activities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('itinerary_day_id');
            $table->string('type')->nullable();
            $table->string('name')->nullable();
            $table->string('time')->nullable();
            $table->float('cost_usd')->default(0);
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->foreign('itinerary_day_id')->references('id')->on('itinerary_days')->onDelete('cascade');
            $table->index(['itinerary_day_id', 'order']);
        });

        Schema::create('itinerary_transports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('itinerary_day_id');
            $table->string('type')->nullable();
            $table->string('vehicle')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->float('distance_km')->nullable();
            $table->string('estimated_time')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->foreign('itinerary_day_id')->references('id')->on('itinerary_days')->onDelete('cascade');
            $table->index(['itinerary_day_id', 'order']);
        });

        Schema::create('itinerary_meals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('itinerary_day_id')->unique();
            $table->string('meal_plan')->nullable();
            $table->boolean('included_breakfast')->default(false);
            $table->boolean('included_lunch')->default(false);
            $table->boolean('included_dinner')->default(false);
            $table->text('dietary_requirements')->nullable();
            $table->timestamps();

            $table->foreign('itinerary_day_id')->references('id')->on('itinerary_days')->onDelete('cascade');
        });

        Schema::create('itinerary_images', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('itinerary_day_id');
            $table->string('image_path');
            $table->string('caption')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->foreign('itinerary_day_id')->references('id')->on('itinerary_days')->onDelete('cascade');
            $table->index(['itinerary_day_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itinerary_images');
        Schema::dropIfExists('itinerary_meals');
        Schema::dropIfExists('itinerary_transports');
        Schema::dropIfExists('itinerary_activities');
        Schema::dropIfExists('itinerary_accommodations');
        Schema::dropIfExists('itinerary_days');
        Schema::dropIfExists('itineraries');
    }
};
