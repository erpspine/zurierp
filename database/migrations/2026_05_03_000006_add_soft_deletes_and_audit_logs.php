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
        if (Schema::hasTable('platform_users') && ! Schema::hasColumn('platform_users', 'deleted_at')) {
            Schema::table('platform_users', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table): void {
                $table->id();
                $table->string('actor_guard')->nullable();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('action');
                $table->string('auditable_type')->nullable();
                $table->string('auditable_id')->nullable();
                $table->json('event_data')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();

                $table->index(['action', 'created_at']);
                $table->index(['auditable_type', 'auditable_id']);
                $table->index(['actor_guard', 'actor_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');

        if (Schema::hasTable('platform_users') && Schema::hasColumn('platform_users', 'deleted_at')) {
            Schema::table('platform_users', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
