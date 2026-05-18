<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('customer_id', 120);

            $table->string('full_name');
            $table->string('email');
            $table->string('phone_whatsapp', 40);
            $table->string('country', 100);
            $table->string('city', 100)->nullable();
            $table->string('lead_source', 120);

            $table->date('travel_start_date');
            $table->date('travel_end_date');
            $table->unsignedInteger('number_of_days')->nullable();
            $table->unsignedInteger('number_of_nights')->nullable();

            $table->unsignedInteger('number_of_pax')->default(1);
            $table->unsignedInteger('adults')->default(1);
            $table->unsignedInteger('children')->default(0);
            $table->unsignedInteger('infants')->default(0);

            $table->json('preferred_destinations');
            $table->string('trip_type', 100);
            $table->string('residency_type', 120);

            $table->string('budget_range', 120);
            $table->decimal('estimated_budget_amount', 14, 2)->nullable();
            $table->string('preferred_vehicle', 120)->nullable();

            $table->string('accommodation_type', 120);
            $table->string('room_preference', 120)->nullable();
            $table->string('meal_plan', 120)->nullable();

            $table->json('activities_interested_in');
            $table->text('special_interests')->nullable();
            $table->text('dietary_requirement')->nullable();
            $table->string('language_preference', 120)->nullable();
            $table->string('guide_preference', 120)->nullable();

            $table->string('lead_status', 60);
            $table->unsignedBigInteger('assigned_sales_person_id');
            $table->string('priority', 30);
            $table->date('follow_up_date');
            $table->time('follow_up_time')->nullable();
            $table->string('next_action', 255);
            $table->string('quotation_status', 60)->nullable();
            $table->unsignedTinyInteger('probability_of_winning')->nullable();

            $table->text('client_request_summary')->nullable();
            $table->text('passport_visa_notes')->nullable();
            $table->text('internal_sales_notes')->nullable();
            $table->text('payment_special_conditions')->nullable();

            $table->json('uploaded_documents')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'lead_status']);
            $table->index(['company_id', 'assigned_sales_person_id']);
            $table->index(['company_id', 'customer_id']);
            $table->index(['company_id', 'follow_up_date']);
            $table->unique(['company_id', 'customer_id', 'email']);

            $table->foreign('assigned_sales_person_id')
                ->references('id')
                ->on('company_users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
