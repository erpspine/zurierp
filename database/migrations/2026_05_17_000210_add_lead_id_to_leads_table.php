<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('lead_id', 120)->nullable()->after('customer_id');
            $table->index(['company_id', 'lead_id']);
            $table->unique(['company_id', 'lead_id']);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropUnique('leads_company_id_lead_id_unique');
            $table->dropIndex('leads_company_id_lead_id_index');
            $table->dropColumn('lead_id');
        });
    }
};
