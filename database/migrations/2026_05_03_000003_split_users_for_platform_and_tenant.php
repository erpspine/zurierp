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
        if (Schema::hasTable('users') && ! Schema::hasTable('platform_users')) {
            Schema::rename('users', 'platform_users');
        }

        if (Schema::hasTable('platform_users')) {
            Schema::table('platform_users', function (Blueprint $table) {
                if (! Schema::hasColumn('platform_users', 'status')) {
                    $table->string('status')->default('active')->after('password');
                }

                if (! Schema::hasColumn('platform_users', 'last_login_at')) {
                    $table->timestamp('last_login_at')->nullable()->after('status');
                }
            });
        }

        if (! Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('status')->default('active');
                $table->string('plan_id')->nullable();
                $table->string('subscription_status')->default('trial');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('company_users')) {
            Schema::create('company_users', function (Blueprint $table) {
                $table->id();
                $table->uuid('company_id');
                $table->string('name');
                $table->string('email');
                $table->string('password');
                $table->string('status')->default('active');
                $table->timestamp('last_login_at')->nullable();
                $table->rememberToken();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->unique(['company_id', 'email']);
            });
        }

        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->uuid('company_id')->nullable();
                $table->string('guard_name');
                $table->string('name');
                $table->string('type');
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
                $table->unique(['company_id', 'guard_name', 'name']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
        Schema::dropIfExists('company_users');
        Schema::dropIfExists('companies');

        if (Schema::hasTable('platform_users') && ! Schema::hasTable('users')) {
            Schema::rename('platform_users', 'users');
        }
    }
};
