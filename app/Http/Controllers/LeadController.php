<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Phone;
use App\Models\City;
use App\Models\State;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LeadController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        // Fetch leads from database
        $leads = Lead::with(['marketer', 'status', 'phones', 'payments'])
            ->where('tenant_id', $tenantId)
            ->latest('last_modified')
            ->limit(50) // Limit for initial load
            ->get();

        // Transform leads to match frontend format
        $transformedLeads = $leads->map(function ($lead) {
            return $this->transformLead($lead);
        });

        // Calculate stats
        $totalLeads = Lead::where('tenant_id', $tenantId)->count();
        $relevantLeads = Lead::where('tenant_id', $tenantId)->where('relevant', true)->count();
        $irrelevantLeads = Lead::where('tenant_id', $tenantId)->where('relevant', false)->count();

        $stats = [
            'total_leads' => $totalLeads,
            'total_leads_change' => '+10%', // TODO: Calculate actual change
            'relevant_this_month' => $relevantLeads . '/' . $totalLeads,
            'relevant_change' => '+10%', // TODO: Calculate actual change
            'cost' => '$5', // TODO: Calculate from payments
            'cost_change' => '+10%', // TODO: Calculate actual change
            'conversion' => '5 out of 50', // TODO: Calculate actual conversion
            'conversion_change' => '-0.89%', // TODO: Calculate actual change
            'close_deal' => 15, // TODO: Calculate from status
            'close_deal_change' => '+10%', // TODO: Calculate actual change
            'paid_to_date' => '$16,000', // TODO: Calculate from payments
            'paid_to_date_change' => '+10%', // TODO: Calculate actual change
            'leads_by_category' => [
                'total' => $totalLeads,
                'relevant' => $relevantLeads,
                'irrelevant' => $irrelevantLeads,
            ]
        ];

        return Inertia::render('Leads/Index', [
            'leads' => $transformedLeads,
            'stats' => $stats,
        ]);
    }

    /**
     * API: Get all leads
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $query = Lead::with(['marketer', 'status', 'phones', 'payments'])
            ->where('tenant_id', $tenantId);

        // Apply filters if provided
        if ($request->has('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        if ($request->has('marketer_id')) {
            $query->where('marketer_id', $request->marketer_id);
        }

        if ($request->has('relevant')) {
            $query->where('relevant', $request->boolean('relevant'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('Shop_Name', 'like', "%{$search}%")
                    ->orWhere('Industry', 'like', "%{$search}%")
                    ->orWhere('City', 'like', "%{$search}%")
                    ->orWhere('State', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $leads = $query->latest('last_modified')->paginate($perPage);

        // Transform leads to match frontend format
        $transformedLeads = $leads->map(function ($lead) {
            return $this->transformLead($lead);
        });

        return response()->json([
            'data' => $transformedLeads,
            'meta' => [
                'current_page' => $leads->currentPage(),
                'last_page' => $leads->lastPage(),
                'per_page' => $leads->perPage(),
                'total' => $leads->total(),
            ],
        ]);
    }

    /**
     * API: Get a single lead
     */
    public function apiShow($id): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $lead = Lead::with(['marketer', 'status', 'phones', 'payments', 'events', 'todolists', 'renewals'])
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        return response()->json([
            'data' => $this->transformLead($lead, true),
        ]);
    }

    /**
     * API: Create a new lead
     */
    public function apiStore(Request $request): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        // Map PascalCase (Flutter) to lowercase/snake_case before validation
        $input = $request->all();
        $mappings = [
            'Name' => 'pic_name',
            'Phone' => 'phone_number',
            'Remarks' => 'remarks',
            'Relevant' => 'relevant',
            'Source' => 'source', // although Source is also in fillable
            'Language' => 'language',
            'Industry' => 'industry',
        ];

        foreach ($mappings as $pascal => $lower) {
            if (isset($input[$pascal]) && !isset($input[$lower])) {
                $input[$lower] = $input[$pascal];
            }
        }

        // Special handling for boolean 'relevant' if sent as string '1'/'0'
        if (isset($input['relevant'])) {
            $input['relevant'] = filter_var($input['relevant'], FILTER_VALIDATE_BOOLEAN);
        }

        $request->merge($input);

        $validated = $request->validate([
            'Shop_Name' => 'required|string|max:255',
            'pic_name' => 'nullable|string|max:255', // P.I.C Name
            'phone_number' => 'nullable|string|max:255', // Phone number
            'Industry' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'Source' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'Language' => 'nullable|string|max:255',
            'language' => 'nullable|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'state_id' => 'nullable|exists:states,id',
            'country_id' => 'nullable|exists:countries,id',
            'City' => 'nullable|string|max:255',
            'State' => 'nullable|string|max:255',
            'Country' => 'nullable|string|max:255',
            'address_1' => 'nullable|string',
            'address_2' => 'nullable|string',
            'address_3' => 'nullable|string',
            'relevant' => 'nullable|boolean',
            'irrelevant_reason_id' => 'nullable|exists:lookups,id',
            'Irrelevant_reason' => 'nullable|string',
            'remarks' => 'nullable|string',
            'marketer_id' => 'nullable|exists:users,id',
            'status_id' => 'nullable|exists:lookups,id',
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['last_modified'] = now();

        // Get city, state, country names from IDs if provided
        if ($request->has('city_id') && $request->city_id) {
            $city = City::with('state.country')->find($request->city_id);
            if ($city) {
                $validated['City'] = $city->name;
                if ($city->state) {
                    $validated['State'] = $city->state->name;
                    if ($city->state->country) {
                        $validated['Country'] = $city->state->country->name;
                    }
                }
            }
        } elseif ($request->has('state_id') && $request->state_id) {
            $state = State::with('country')->find($request->state_id);
            if ($state) {
                $validated['State'] = $state->name;
                if ($state->country) {
                    $validated['Country'] = $state->country->name;
                }
            }
        } elseif ($request->has('country_id') && $request->country_id) {
            $country = Country::find($request->country_id);
            if ($country) {
                $validated['Country'] = $country->name;
            }
        }

        // Get irrelevant reason label from lookup if provided
        if ($request->has('irrelevant_reason_id') && $request->irrelevant_reason_id) {
            $lookup = \App\Models\Lookup::find($request->irrelevant_reason_id);
            if ($lookup) {
                $validated['Irrelevant_reason'] = $lookup->label ?? $lookup->name;
            }
        }

        $lead = Lead::create($validated);

        // Handle multiple phone records (contacts)
        $phones = $request->Phones ?? $request->phones;
        if (!is_array($phones) && ($request->phone_number ?? $request->Phone)) {
            // Fallback to single phone if it's not a list
            $phones = [[
                'phone_number' => $request->phone_number ?? $request->Phone,
                'name' => $request->pic_name ?? $request->Name,
                'is_main' => true
            ]];
        }

        if (is_array($phones)) {
            foreach ($phones as $phoneData) {
                Phone::create([
                    'lead_id' => $lead->id,
                    'name' => $phoneData['name'] ?? null,
                    'phone_number' => $phoneData['phone_number'] ?? '',
                    'is_main' => (bool) ($phoneData['is_main'] ?? false),
                    'tenant_id' => $tenantId,
                ]);
            }
        }

        // Handle marketer association via LeadPIC
        $marketerId = $request->marketer_id ?? $request->Marketer;
        if ($marketerId) {
            \App\Models\Picable::create([
                'picable_type' => \App\Models\Lead::class,
                'picable_id' => $lead->id,
                'user_id' => $marketerId,
                'tenant_id' => $tenantId,
            ]);
        }

        $lead->load(['marketer', 'status', 'phones', 'payments']);

        return response()->json([
            'data' => $this->transformLead($lead),
            'message' => 'Lead created successfully',
        ], 201);
    }

    /**
     * API: Update a lead
     */
    public function apiUpdate(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $lead = Lead::where('tenant_id', $tenantId)->findOrFail($id);

        // Map PascalCase (Flutter) to lowercase/snake_case before validation
        $input = $request->all();
        $mappings = [
            'Name' => 'pic_name',
            'Phone' => 'phone_number',
            'Remarks' => 'remarks',
            'Relevant' => 'relevant',
            'Source' => 'source',
            'Language' => 'language',
            'Industry' => 'industry',
        ];

        foreach ($mappings as $pascal => $lower) {
            if (isset($input[$pascal]) && !isset($input[$lower])) {
                $input[$lower] = $input[$pascal];
            }
        }

        // Special handling for boolean 'relevant' if sent as string '1'/'0'
        if (isset($input['relevant'])) {
            $input['relevant'] = filter_var($input['relevant'], FILTER_VALIDATE_BOOLEAN);
        }

        $request->merge($input);

        $validated = $request->validate([
            'Shop_Name' => 'sometimes|required|string|max:255',
            'pic_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'Industry' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'Source' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'Language' => 'nullable|string|max:255',
            'language' => 'nullable|string|max:255',
            'City' => 'nullable|string|max:255',
            'State' => 'nullable|string|max:255',
            'Country' => 'nullable|string|max:255',
            'address_1' => 'nullable|string',
            'address_2' => 'nullable|string',
            'address_3' => 'nullable|string',
            'relevant' => 'nullable|boolean',
            'Irrelevant_reason' => 'nullable|string',
            'remarks' => 'nullable|string',
            'marketer_id' => 'nullable|exists:users,id',
            'status_id' => 'nullable|exists:lookups,id',
        ]);

        $validated['last_modified'] = now();
        $lead->update($validated);

        // Handle multiple phone records (contacts)
        $phones = $request->Phones ?? $request->phones;
        if (!is_array($phones) && ($request->phone_number ?? $request->Phone)) {
             // Fallback for single phone updating
             $phones = [[
                 'phone_number' => $request->phone_number ?? $request->Phone,
                 'name' => $request->pic_name ?? $request->Name,
                 'is_main' => true
             ]];
        }

        if (is_array($phones)) {
            // Rebuild the phones list for simplicity
            Phone::where('lead_id', $lead->id)->delete();
            foreach ($phones as $phoneData) {
                Phone::create([
                    'lead_id' => $lead->id,
                    'name' => $phoneData['name'] ?? null,
                    'phone_number' => $phoneData['phone_number'] ?? '',
                    'is_main' => (bool) ($phoneData['is_main'] ?? false),
                    'tenant_id' => $tenantId,
                ]);
            }
        }

        // Update or create marketer via LeadPIC
        $marketerId = $request->marketer_id ?? $request->Marketer;
        if ($marketerId) {
            \App\Models\Picable::updateOrCreate(
                ['picable_type' => \App\Models\Lead::class, 'picable_id' => $lead->id],
                ['user_id' => $marketerId, 'tenant_id' => $tenantId]
            );
        }

        $lead->load(['marketer', 'status', 'phones', 'payments']);

        return response()->json([
            'data' => $this->transformLead($lead),
            'message' => 'Lead updated successfully',
        ]);
    }

    /**
     * API: Delete a lead
     */
    public function apiDestroy($id): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $lead = Lead::where('tenant_id', $tenantId)->findOrFail($id);
        $lead->delete();

        return response()->json([
            'message' => 'Lead deleted successfully',
        ]);
    }

    /**
     * API: Get lead statistics
     */
    public function apiStats(Request $request): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $totalLeads = Lead::where('tenant_id', $tenantId)->count();
        $relevantLeads = Lead::where('tenant_id', $tenantId)->where('relevant', true)->count();
        $irrelevantLeads = Lead::where('tenant_id', $tenantId)->where('relevant', false)->count();

        // Get leads by industry/category
        $leadsByCategory = Lead::where('tenant_id', $tenantId)
            ->selectRaw('Industry as category, COUNT(*) as total')
            ->groupBy('category')
            ->get()
            ->map(function ($item) use ($tenantId) {
                $relevant = Lead::where('tenant_id', $tenantId)
                    ->where('relevant', true)
                    ->where('Industry', $item->category)
                    ->count();

                return [
                    'category' => $item->category ?? 'Uncategorized',
                    'total' => $item->total,
                    'relevant' => $relevant,
                    'irrelevant' => $item->total - $relevant,
                ];
            });

        return response()->json([
            'data' => [
                'total_leads' => $totalLeads,
                'relevant_leads' => $relevantLeads,
                'irrelevant_leads' => $irrelevantLeads,
                'leads_by_category' => $leadsByCategory,
            ],
        ]);
    }

    /**
     * Transform lead data to match frontend format
     */
    private function transformLead(Lead $lead, bool $includeDetails = false): array
    {
        // Get primary contact info (main phone number)
        $primaryPhone = $lead->phones->where('is_main', true)->first() ?? $lead->phones->first();
        $phoneNumber = $primaryPhone ? $primaryPhone->phone_number : null;
        $contactName = $primaryPhone ? $primaryPhone->name : null;

        // Get payment status
        $latestPayment = $lead->payments->sortByDesc('created_at')->first();
        $paymentStatus = $latestPayment && $latestPayment->status ? ($latestPayment->status->label ?? 'None') : 'None';

        // Build location string
        $locationParts = array_filter([
            $lead->address_1,
            $lead->City,
            $lead->State,
            $lead->Country,
        ]);
        $location = implode(', ', $locationParts);

        // Determine source type
        $source = $lead->Source ?? 'Unknown';
        $sourceType = strtolower($source);
        $sourceType = str_replace([' ', '小红书'], ['', 'xiaohongshu'], $sourceType);

        // Format last modified
        $lastModified = $lead->last_modified 
            ? $lead->last_modified->format('d M Y') 
            : ($lead->updated_at ? $lead->updated_at->format('d M Y') : null);

        // Add modifier initials if marketer exists
        if ($lead->marketer && $lastModified) {
            $initials = $this->getInitials($lead->marketer->name);
            $lastModified = $initials . ' ' . $lastModified;
        }

        $data = [
            // Standard/Web structure
            'id' => $lead->id,
            'contact_info' => [
                'phone' => $phoneNumber,
                'name' => $contactName,
            ],
            'shop_info' => [
                'name' => $lead->Shop_Name,
                'category' => $lead->Industry ?? 'Uncategorized',
            ],
            'location' => [
                'address' => $lead->address_1 ?? '',
                'city' => $location,
            ],
            'source' => $source,
            'source_type' => $sourceType,
            'relevancy' => $lead->relevant ? 'Relevant' : 'Irrelevant',
            'status' => $lead->status->label ?? 'Unknown',
            'payment' => $paymentStatus,
            'last_modified' => $lastModified,

            // Flutter Model Compatible structure (PascalCase)
            'ID' => $lead->id,
            'Name' => $contactName ?? '',
            'Shop_Name' => $lead->Shop_Name,
            'Phone' => $phoneNumber ?? '',
            'State' => $lead->State ?? '',
            'City' => $lead->City ?? '',
            'Country' => $lead->Country ?? '',
            'Industry' => $lead->Industry ?? '',
            'Marketer' => $lead->marketer ? $lead->marketer->id : '',
            'Relevant' => $lead->relevant ? '1' : '0',
            'Source' => $source,
            'Remarks' => $lead->remarks ?? '',
            'Language' => $lead->Language ?? '',
            'LastUpdateDate' => $lead->last_modified ? $lead->last_modified->toIso8601String() : $lead->updated_at->toIso8601String(),
            'lead_status' => $lead->status ? ['id' => $lead->status->id, 'status' => $lead->status->label] : null,
            'lead_payment' => $latestPayment && $latestPayment->status ? ['id' => $latestPayment->status->id, 'payment' => $latestPayment->status->label] : null,
            'Phones' => $lead->phones->map(function ($phone) {
                return [
                    'id' => $phone->id,
                    'name' => $phone->name,
                    'phone_number' => $phone->phone_number,
                    'is_main' => $phone->is_main,
                ];
            }),
        ];

        if ($includeDetails) {
            $data['details'] = [
                'address_1' => $lead->address_1,
                'address_2' => $lead->address_2,
                'address_3' => $lead->address_3,
                'city' => $lead->City,
                'state' => $lead->State,
                'country' => $lead->Country,
                'language' => $lead->Language,
                'irrelevant_reason' => $lead->Irrelevant_reason,
                'remarks' => $lead->remarks,
                'marketer' => $lead->marketer ? [
                    'id' => $lead->marketer->id,
                    'name' => $lead->marketer->name,
                    'email' => $lead->marketer->email,
                ] : null,
                'status_detail' => $lead->status ? [
                    'id' => $lead->status->id,
                    'name' => $lead->status->name,
                    'label' => $lead->status->label,
                ] : null,
                'phones' => $lead->phones->map(function ($phone) {
                    return [
                        'id' => $phone->id,
                        'name' => $phone->name,
                        'phone_number' => $phone->phone_number,
                        'is_main' => $phone->is_main,
                    ];
                }),
                'payments' => $lead->payments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'amount' => $payment->Amount,
                        'currency' => $payment->currency ? $payment->currency->name : null,
                        'status' => $payment->status ? $payment->status->label : null,
                    ];
                }),
                'events_count' => $lead->events->count(),
                'todolists_count' => $lead->todolists->count(),
                'renewals_count' => $lead->renewals->count(),
            ];
        }

        return $data;
    }

    /**
     * Get initials from a name
     */
    private function getInitials(string $name): string
    {
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
        }
        return substr($initials, 0, 2);
    }
}





