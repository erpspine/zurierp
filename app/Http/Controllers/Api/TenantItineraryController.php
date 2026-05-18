<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Itinerary;
use App\Models\Lead;
use App\Models\CompanyUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenantItineraryController extends Controller
{
    private function currentTenantUser(Request $request): CompanyUser
    {
        /** @var CompanyUser $user */
        $user = $request->user();

        return $user;
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->currentTenantUser($request);

        $itineraries = Itinerary::query()
            ->where('company_id', $user->company_id)
            ->with(['createdBy:id,name,email', 'lead:id,lead_id,full_name', 'days:id,itinerary_id,day_number,title,date'])
            ->latest()
            ->paginate(20);

        return response()->json($itineraries);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->currentTenantUser($request);

        $data = $request->validate([
            'lead_id' => [
                'required',
                'uuid',
                Rule::exists('leads', 'id')->where('company_id', $user->company_id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
        ]);

        $data['company_id'] = $user->company_id;
        $data['created_by'] = $user->id;

        $itinerary = Itinerary::query()->create($data);

        return response()->json([
            'message' => 'Itinerary created successfully.',
            'itinerary' => $itinerary->load('createdBy:id,name,email', 'lead:id,lead_id,full_name'),
        ], 201);
    }

    public function show(Request $request, Itinerary $itinerary): JsonResponse
    {
        $user = $this->currentTenantUser($request);

        if ($itinerary->company_id !== $user->company_id) {
            abort(404);
        }

        $itinerary->load([
            'createdBy:id,name,email',
            'lead:id,lead_id,full_name,email',
            'days' => function ($query) {
                $query->orderBy('day_number')
                    ->with([
                        'accommodations',
                        'activities' => fn ($q) => $q->orderBy('order'),
                        'transports' => fn ($q) => $q->orderBy('order'),
                        'meals',
                        'images' => fn ($q) => $q->orderBy('order'),
                    ]);
            },
        ]);

        return response()->json($itinerary);
    }

    public function update(Request $request, Itinerary $itinerary): JsonResponse
    {
        $user = $this->currentTenantUser($request);

        if ($itinerary->company_id !== $user->company_id) {
            abort(404);
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
        ]);

        $itinerary->update(array_filter($data, fn ($v) => $v !== null));

        return response()->json([
            'message' => 'Itinerary updated successfully.',
            'itinerary' => $itinerary->fresh()->load('createdBy:id,name,email', 'lead:id,lead_id,full_name'),
        ]);
    }

    public function destroy(Request $request, Itinerary $itinerary): JsonResponse
    {
        $user = $this->currentTenantUser($request);

        if ($itinerary->company_id !== $user->company_id) {
            abort(404);
        }

        $itinerary->delete();

        return response()->json(['message' => 'Itinerary deleted successfully.']);
    }
}
