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
        if (! Schema::hasTable('platform_user_roles')) {
            Schema::create('platform_user_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('platform_user_id')->constrained('platform_users')->cascadeOnDelete();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['platform_user_id', 'role_id']);
            });
        }

        if (! Schema::hasTable('company_user_roles')) {
            Schema::create('company_user_roles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_user_id')->constrained('company_users')->cascadeOnDelete();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['company_user_id', 'role_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_user_roles');
        Schema::dropIfExists('platform_user_roles');
    }
};
