<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trusted_devices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('guard', 30);
            $table->unsignedBigInteger('user_id');
            $table->uuid('company_id')->nullable();
            $table->string('device_hash', 64);
            $table->string('device_name', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['guard', 'user_id', 'device_hash']);
            $table->index(['guard', 'user_id']);
        });

        Schema::create('login_otp_challenges', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('guard', 30);
            $table->unsignedBigInteger('user_id');
            $table->uuid('company_id')->nullable();
            $table->string('device_hash', 64);
            $table->string('device_name', 255)->nullable();
            $table->string('otp_code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['guard', 'user_id']);
            $table->index(['guard', 'user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_otp_challenges');
        Schema::dropIfExists('trusted_devices');
    }
};