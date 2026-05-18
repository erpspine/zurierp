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
        Schema::table('companies', function (Blueprint $table): void {
            if (! Schema::hasColumn('companies', 'legal_name')) {
                $table->string('legal_name')->nullable()->after('name');
            }

            if (! Schema::hasColumn('companies', 'company_code')) {
                $table->string('company_code', 30)->unique()->nullable()->after('legal_name');
            }

            if (! Schema::hasColumn('companies', 'registration_number')) {
                $table->string('registration_number')->nullable()->after('company_code');
            }

            if (! Schema::hasColumn('companies', 'tin')) {
                $table->string('tin')->nullable()->after('registration_number');
            }

            if (! Schema::hasColumn('companies', 'vat_number')) {
                $table->string('vat_number')->nullable()->after('tin');
            }

            if (! Schema::hasColumn('companies', 'industry')) {
                $table->string('industry')->nullable()->after('vat_number');
            }

            if (! Schema::hasColumn('companies', 'business_type')) {
                $table->string('business_type')->nullable()->after('industry');
            }

            if (! Schema::hasColumn('companies', 'incorporation_date')) {
                $table->date('incorporation_date')->nullable()->after('business_type');
            }

            // Address
            if (! Schema::hasColumn('companies', 'country')) {
                $table->string('country')->nullable()->after('incorporation_date');
            }

            if (! Schema::hasColumn('companies', 'region')) {
                $table->string('region')->nullable()->after('country');
            }

            if (! Schema::hasColumn('companies', 'city')) {
                $table->string('city')->nullable()->after('region');
            }

            if (! Schema::hasColumn('companies', 'address_line_1')) {
                $table->string('address_line_1')->nullable()->after('city');
            }

            if (! Schema::hasColumn('companies', 'address_line_2')) {
                $table->string('address_line_2')->nullable()->after('address_line_1');
            }

            if (! Schema::hasColumn('companies', 'postal_code')) {
                $table->string('postal_code', 30)->nullable()->after('address_line_2');
            }

            if (! Schema::hasColumn('companies', 'google_map_location')) {
                $table->string('google_map_location', 500)->nullable()->after('postal_code');
            }

            // Contact
            if (! Schema::hasColumn('companies', 'phone')) {
                $table->string('phone', 40)->nullable()->after('google_map_location');
            }

            if (! Schema::hasColumn('companies', 'alt_phone')) {
                $table->string('alt_phone', 40)->nullable()->after('phone');
            }

            if (! Schema::hasColumn('companies', 'email')) {
                $table->string('email')->nullable()->after('alt_phone');
            }

            if (! Schema::hasColumn('companies', 'website')) {
                $table->string('website')->nullable()->after('email');
            }

            if (! Schema::hasColumn('companies', 'whatsapp')) {
                $table->string('whatsapp', 40)->nullable()->after('website');
            }

            // Branding
            if (! Schema::hasColumn('companies', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('whatsapp');
            }

            if (! Schema::hasColumn('companies', 'email_logo_path')) {
                $table->string('email_logo_path')->nullable()->after('logo_path');
            }

            if (! Schema::hasColumn('companies', 'document_logo_path')) {
                $table->string('document_logo_path')->nullable()->after('email_logo_path');
            }

            // Finance
            if (! Schema::hasColumn('companies', 'default_currency')) {
                $table->string('default_currency', 10)->default('USD')->after('document_logo_path');
            }

            if (! Schema::hasColumn('companies', 'multi_currency_enabled')) {
                $table->boolean('multi_currency_enabled')->default(false)->after('default_currency');
            }

            if (! Schema::hasColumn('companies', 'financial_year_start')) {
                $table->unsignedTinyInteger('financial_year_start')->default(1)->after('multi_currency_enabled');
            }

            if (! Schema::hasColumn('companies', 'tax_enabled')) {
                $table->boolean('tax_enabled')->default(false)->after('financial_year_start');
            }

            // Notifications
            if (! Schema::hasColumn('companies', 'notify_email')) {
                $table->boolean('notify_email')->default(true)->after('tax_enabled');
            }

            if (! Schema::hasColumn('companies', 'notify_whatsapp')) {
                $table->boolean('notify_whatsapp')->default(false)->after('notify_email');
            }

            if (! Schema::hasColumn('companies', 'notify_sms')) {
                $table->boolean('notify_sms')->default(false)->after('notify_whatsapp');
            }

            if (! Schema::hasColumn('companies', 'notify_on')) {
                $table->json('notify_on')->nullable()->after('notify_sms');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn([
                'legal_name', 'company_code', 'registration_number', 'tin', 'vat_number',
                'industry', 'business_type', 'incorporation_date',
                'country', 'region', 'city', 'address_line_1', 'address_line_2',
                'postal_code', 'google_map_location',
                'phone', 'alt_phone', 'email', 'website', 'whatsapp',
                'logo_path', 'email_logo_path', 'document_logo_path',
                'default_currency', 'multi_currency_enabled', 'financial_year_start', 'tax_enabled',
                'notify_email', 'notify_whatsapp', 'notify_sms', 'notify_on',
            ]);
        });
    }
};
