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
        if (Schema::hasTable('platform_users') && ! Schema::hasColumn('platform_users', 'api_token')) {
            Schema::table('platform_users', function (Blueprint $table): void {
                $table->string('api_token', 64)->nullable()->unique()->after('remember_token');
            });
        }

        if (Schema::hasTable('company_users') && ! Schema::hasColumn('company_users', 'api_token')) {
            Schema::table('company_users', function (Blueprint $table): void {
                $table->string('api_token', 64)->nullable()->unique()->after('remember_token');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('company_users') && Schema::hasColumn('company_users', 'api_token')) {
            Schema::table('company_users', function (Blueprint $table): void {
                $table->dropUnique(['api_token']);
                $table->dropColumn('api_token');
            });
        }

        if (Schema::hasTable('platform_users') && Schema::hasColumn('platform_users', 'api_token')) {
            Schema::table('platform_users', function (Blueprint $table): void {
                $table->dropUnique(['api_token']);
                $table->dropColumn('api_token');
            });
        }
    }
};
