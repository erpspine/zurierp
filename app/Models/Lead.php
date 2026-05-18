<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'company_id',
        'customer_id',
        'lead_id',
        'full_name',
        'email',
        'phone_whatsapp',
        'country',
        'city',
        'lead_source',
        'travel_start_date',
        'travel_end_date',
        'number_of_days',
        'number_of_nights',
        'number_of_pax',
        'adults',
        'children',
        'infants',
        'preferred_destinations',
        'trip_type',
        'residency_type',
        'budget_range',
        'estimated_budget_amount',
        'preferred_vehicle',
        'accommodation_type',
        'room_preference',
        'meal_plan',
        'activities_interested_in',
        'special_interests',
        'dietary_requirement',
        'language_preference',
        'guide_preference',
        'lead_status',
        'assigned_sales_person_id',
        'priority',
        'follow_up_date',
        'follow_up_time',
        'next_action',
        'quotation_status',
        'probability_of_winning',
        'client_request_summary',
        'passport_visa_notes',
        'internal_sales_notes',
        'payment_special_conditions',
        'uploaded_documents',
    ];

    protected function casts(): array
    {
        return [
            'travel_start_date' => 'date',
            'travel_end_date' => 'date',
            'follow_up_date' => 'date',
            'follow_up_time' => 'datetime:H:i:s',
            'estimated_budget_amount' => 'decimal:2',
            'preferred_destinations' => 'array',
            'activities_interested_in' => 'array',
            'uploaded_documents' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignedSalesPerson(): BelongsTo
    {
        return $this->belongsTo(CompanyUser::class, 'assigned_sales_person_id');
    }
}
