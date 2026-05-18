<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->string('invoice_number', 60)->nullable()->unique()->after('payment_date');
            $table->timestamp('invoice_generated_at')->nullable()->after('invoice_number');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn(['invoice_generated_at', 'invoice_number']);
        });
    }
};
