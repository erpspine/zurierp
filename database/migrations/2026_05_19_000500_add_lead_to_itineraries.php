<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itineraries', function (Blueprint $table): void {
            if (! Schema::hasColumn('itineraries', 'lead_id')) {
                $table->uuid('lead_id')->after('company_id');
                $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
                $table->index(['lead_id', 'created_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('itineraries', function (Blueprint $table): void {
            $table->dropForeignKeyIfExists(['lead_id']);
            $table->dropIndexIfExists(['lead_id', 'created_at']);
            $table->dropColumn('lead_id');
        });
    }
};
