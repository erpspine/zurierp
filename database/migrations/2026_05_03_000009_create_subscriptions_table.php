<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();

            // License
            $table->string('license_key', 60)->unique();

            // Subscription period
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'semi_annual', 'annual'])->default('monthly');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->enum('status', ['active', 'trial', 'expired', 'cancelled', 'suspended'])->default('trial');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Payment
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'card', 'cheque', 'other'])->nullable();
            $table->string('payment_reference', 200)->nullable(); // TxID / cheque no. / receipt no.
            $table->text('payment_notes')->nullable();
            $table->date('payment_date')->nullable();

            // Who created it
            $table->unsignedBigInteger('created_by')->nullable(); // platform_users.id
            $table->string('created_by_name', 200)->nullable();   // denormalised for display

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
