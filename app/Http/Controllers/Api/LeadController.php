<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Models\BusinessType;
use App\Models\MonthlySalesVolume;
use App\Models\CurrentSystem;
use App\Models\LeadHistory;
use App\Models\LeadStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Http\Requests\LeadStoreRequest;


class LeadController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/leads",
     *     summary="Get leads list",
     *     description="Retrieve a paginated list of leads with filtering options",
     *     operationId="getLeadsList",
     *     tags={"Leads"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="shop_name",
     *         in="query",
     *         description="Filter by shop name, contact person, email, or mobile number",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="contact_person",
     *         in="query",
     *         description="Filter by contact person name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="mobile_number",
     *         in="query",
     *         description="Filter by mobile number",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="Filter by email address",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="area_locality",
     *         in="query",
     *         description="Filter by area or locality",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="business_type",
     *         in="query",
     *         description="Filter by business type ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="current_system",
     *         in="query",
     *         description="Filter by current system ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="lead_status",
     *         in="query",
     *         description="Filter by lead status ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="assigned_to",
     *         in="query",
     *         description="Filter by assigned user ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="created_by",
     *         in="query",
     *         description="Filter by creator user ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="next_follow_up_date",
     *         in="query",
     *         description="Filter by next follow-up date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Filter by creation date range start (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Filter by creation date range end (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Leads retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Lead")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="last_page", type="integer"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Lead::with([
            'createdByUser',
            'assignedToUser',
            'lastUpdatedByUser',
            'businessTypeData',
            'currentSystemData',
            'leadStatusData'
        ]);

        // Log the incoming request parameters for debugging
        Log::info('LeadController@index called with parameters: ', $request->all());

        // Filtering
        if ($request->filled('shop_name')) {
            $query->where(function ($q) use ($request) {
                $q->where('shop_name', 'like', '%' . $request->shop_name . '%')
                    ->orWhere('contact_person', 'like', '%' . $request->shop_name . '%')
                    ->orWhere('email', 'like', '%' . $request->shop_name . '%')
                    ->orWhere('mobile_number', 'like', '%' . $request->shop_name . '%');
            });
        }

        if ($request->filled('contact_person')) {
            $query->where('contact_person', 'like', '%' . $request->contact_person . '%');
        }

        if ($request->filled('mobile_number')) {
            $query->where('mobile_number', 'like', '%' . $request->mobile_number . '%');
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }

        if ($request->filled('area_locality')) {
            $query->where('area_locality', 'like', '%' . $request->area_locality . '%');
        }

        if ($request->filled('business_type')) {
            $query->where('business_type', $request->business_type);
        }

        if ($request->filled('current_system')) {
            $query->where('current_system', $request->current_system);
        }

        if ($request->filled('lead_status')) {
            $query->where('lead_status', $request->lead_status);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        // Date filtering
        if ($request->filled('next_follow_up_date')) {
            $query->whereDate('next_follow_up_date', $request->next_follow_up_date);
        }

        // Date range filtering for created_at
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59',
            ]);
        }

        // Pagination (default: 10 per page)
        $perPage = $request->integer('per_page', 10);
        $leads = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($leads);
    }

    /**
     * Get the count of leads. Filter by status, business type, assigned to, created by, prospect rating, next follow up date, and date range.
     */
    public function getLeadsCount(Request $request)
    {
        $query = Lead::query();

        // Filtering by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        // Filtering by status if needed
        if ($request->filled('lead_status')) {
            $query->where('lead_status', $request->lead_status);
        }

        $totalLeads = $query->count();

        $completedLeads = (clone $query)
            ->whereNotNull('completed_at')
            ->count();

        return response()->json([
            'total_leads' => $totalLeads,
            'completed_leads' => $completedLeads,
        ]);
    }

    /**
     * Store a newly created lead in storage.
     */
    // public function store(Request $request)
    // {
    //     try {
    //     $validated = $request->validate([
    //         'shop_name' => 'required|string|max:255',
    //         'contact_person' => 'required|string|max:255',
    //         'mobile_number' => 'required|string|max:20',
    //         'alternate_number' => 'nullable|string|max:20',
    //         'email' => 'nullable|email|max:255',
    //         'address' => 'required|string|max:500',
    //         'area_locality' => 'required|string|max:255',
    //         'pincode' => 'required|string|max:10',
    //         'gps_location' => 'required|string|max:255',
    //         'business_type' => 'required|exists:business_types,id',
    //         'current_system' => 'required|exists:current_systems,id',
    //         'lead_status' => 'required|exists:lead_statuses,id',
    //         'plan_interest' => 'required|string|max:255',
    //         'next_follow_up_date' => 'required|date',
    //         'meeting_notes' => 'nullable|string',
    //         'assigned_to' => 'required|exists:users,id',
    //     ]);

    //     // Set the created_by and last_updated_by to the authenticated user
    //     $validated['created_by'] = auth()->user()->id ?? 1; // Fallback to user ID 1 if no auth
    //     $validated['last_updated_by'] = auth()->user()->id ?? 1;

    //     $lead = Lead::create($validated);
    //     $lead->load([
    //         'createdByUser',
    //         'assignedToUser',
    //         'lastUpdatedByUser',
    //         'businessTypeData',
    //         'currentSystemData',
    //         'leadStatusData'
    //     ]);
    //     } catch (\Throwable $e) {
    //         Log::error('Error creating lead: ' . $e->getMessage());
    //         return response()->json(['message' => 'Failed to create lead', 'error' => $e->getMessage()], 500);
    //     }

    //     return response()->json($lead, 201);
    // }

    /**
     * @OA\Post(
     *     path="/api/leads",
     *     summary="Create a new lead",
     *     description="Create a new lead with all required information",
     *     operationId="createLead",
     *     tags={"Leads"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"shop_name", "contact_person", "mobile_number", "address", "area_locality", "pincode", "business_type", "current_system", "lead_status", "plan_interest", "assigned_to"},
     *             @OA\Property(property="shop_name", type="string", example="ABC Electronics", description="Name of the shop/business"),
     *             @OA\Property(property="contact_person", type="string", example="John Doe", description="Contact person name"),
     *             @OA\Property(property="mobile_number", type="string", example="9876543210", description="Primary mobile number"),
     *             @OA\Property(property="alternate_number", type="string", example="9876543211", description="Alternate mobile number", nullable=true),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email address", nullable=true),
     *             @OA\Property(property="address", type="string", example="123 Main Street, City", description="Business address"),
     *             @OA\Property(property="area_locality", type="string", example="Downtown", description="Area or locality"),
     *             @OA\Property(property="pincode", type="string", example="123456", description="Pincode"),
     *             @OA\Property(property="branches", type="integer", example=2, description="Number of branches", nullable=true),
     *             @OA\Property(property="gps_location", type="string", example="28.6139,77.2090", description="GPS coordinates", nullable=true),
     *             @OA\Property(property="business_type", type="integer", example=1, description="Business type ID"),
     *             @OA\Property(property="current_system", type="integer", example=1, description="Current system ID"),
     *             @OA\Property(property="lead_status", type="integer", example=1, description="Lead status ID"),
     *             @OA\Property(property="plan_interest", type="string", example="Premium Plan", description="Plan of interest"),
     *             @OA\Property(property="next_follow_up_date", type="string", format="date", example="2024-01-15", description="Next follow-up date", nullable=true),
     *             @OA\Property(property="meeting_notes", type="string", example="Initial discussion completed", description="Meeting notes", nullable=true),
     *             @OA\Property(property="assigned_to", type="integer", example=2, description="User ID to assign the lead to")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Lead created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="shop_name", type="string", example="ABC Electronics"),
     *             @OA\Property(property="contact_person", type="string", example="John Doe"),
     *             @OA\Property(property="mobile_number", type="string", example="9876543210"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
     *             @OA\Property(property="created_by_user", type="object", ref="#/components/schemas/User"),
     *             @OA\Property(property="assigned_to_user", type="object", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object", example={"shop_name": {"The shop name field is required."}})
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function store(LeadStoreRequest $request)
    {
        $data = $request->validated();

        $userId = optional($request->user())->id ?? 1;
        $data['created_by'] = $userId;
        $data['last_updated_by'] = $userId;

        try {
            $lead = Lead::create($data);
            $lead->load([
                'createdByUser',
                'assignedToUser',
                'lastUpdatedByUser',
                'businessTypeData',
                'currentSystemData',
                'leadStatusData'
            ]);

            return response()->json($lead, 201);
        } catch (\Throwable $e) {
            Log::error('Error creating lead: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create lead',
                'error'   => app()->isProduction() ? null : $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/leads/{lead}",
     *     summary="Get lead details",
     *     description="Retrieve detailed information about a specific lead including all related data",
     *     operationId="getLeadDetails",
     *     tags={"Leads"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="lead",
     *         in="path",
     *         description="Lead ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lead details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="shop_name", type="string", example="ABC Electronics"),
     *             @OA\Property(property="contact_person", type="string", example="John Doe"),
     *             @OA\Property(property="mobile_number", type="string", example="9876543210"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="address", type="string", example="123 Main Street, City"),
     *             @OA\Property(property="area_locality", type="string", example="Downtown"),
     *             @OA\Property(property="pincode", type="string", example="123456"),
     *             @OA\Property(property="branches", type="integer", example=2),
     *             @OA\Property(property="gps_location", type="string", example="28.6139,77.2090"),
     *             @OA\Property(property="plan_interest", type="string", example="Premium Plan"),
     *             @OA\Property(property="next_follow_up_date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="meeting_notes", type="string", example="Initial discussion completed"),
     *             @OA\Property(property="completed_at", type="string", format="date-time", example="2024-01-15T15:30:00Z", nullable=true),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
     *             @OA\Property(property="created_by_user", type="object", ref="#/components/schemas/User"),
     *             @OA\Property(property="assigned_to_user", type="object", ref="#/components/schemas/User"),
     *             @OA\Property(property="last_updated_by_user", type="object", ref="#/components/schemas/User"),
     *             @OA\Property(property="business_type_data", type="object", example={"id": 1, "name": "Retail"}),
     *             @OA\Property(property="current_system_data", type="object", example={"id": 1, "name": "Manual"}),
     *             @OA\Property(property="lead_status_data", type="object", example={"id": 1, "name": "New"}),
     *             @OA\Property(property="recorded_audios", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="selfies", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="shop_photos", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="histories", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="follow_ups", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="meetings", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lead not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function show(Lead $lead)
    {
        $lead->load([
            'createdByUser',
            'assignedToUser',
            'lastUpdatedByUser',
            'businessTypeData',
            'currentSystemData',
            'leadStatusData',
            'recordedAudios',
            'selfies',
            'shopPhotos',
            'histories.updatedBy',
            'followUps',
            'meetings',
            'meetings.recordedAudios',
            'meetings.selfies',
            'meetings.shopPhotos',
        ]);

        return response()->json($lead);
    }

    /**
     * @OA\Put(
     *     path="/api/leads/{lead}",
     *     summary="Update lead",
     *     description="Update an existing lead with new information",
     *     operationId="updateLead",
     *     tags={"Leads"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="lead",
     *         in="path",
     *         description="Lead ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="shop_name", type="string", example="ABC Electronics Updated", description="Name of the shop/business"),
     *             @OA\Property(property="contact_person", type="string", example="John Doe", description="Contact person name"),
     *             @OA\Property(property="mobile_number", type="string", example="9876543210", description="Primary mobile number"),
     *             @OA\Property(property="alternate_number", type="string", example="9876543211", description="Alternate mobile number", nullable=true),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email address", nullable=true),
     *             @OA\Property(property="address", type="string", example="123 Main Street, City", description="Business address"),
     *             @OA\Property(property="area_locality", type="string", example="Downtown", description="Area or locality"),
     *             @OA\Property(property="pincode", type="string", example="123456", description="Pincode"),
     *             @OA\Property(property="branches", type="integer", example=2, description="Number of branches", nullable=true),
     *             @OA\Property(property="gps_location", type="string", example="28.6139,77.2090", description="GPS coordinates", nullable=true),
     *             @OA\Property(property="business_type", type="integer", example=1, description="Business type ID"),
     *             @OA\Property(property="current_system", type="integer", example=1, description="Current system ID"),
     *             @OA\Property(property="lead_status", type="integer", example=2, description="Lead status ID"),
     *             @OA\Property(property="plan_interest", type="string", example="Premium Plan", description="Plan of interest"),
     *             @OA\Property(property="next_follow_up_date", type="string", format="date", example="2024-01-20", description="Next follow-up date", nullable=true),
     *             @OA\Property(property="meeting_notes", type="string", example="Follow-up discussion completed", description="Meeting notes", nullable=true),
     *             @OA\Property(property="assigned_to", type="integer", example=2, description="User ID to assign the lead to"),
     *             @OA\Property(property="action", type="string", example="Status Updated", description="Action taken", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lead updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="shop_name", type="string", example="ABC Electronics Updated"),
     *             @OA\Property(property="contact_person", type="string", example="John Doe"),
     *             @OA\Property(property="mobile_number", type="string", example="9876543210"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T12:00:00Z"),
     *             @OA\Property(property="created_by_user", type="object", ref="#/components/schemas/User"),
     *             @OA\Property(property="assigned_to_user", type="object", ref="#/components/schemas/User"),
     *             @OA\Property(property="last_updated_by_user", type="object", ref="#/components/schemas/User"),
     *             @OA\Property(property="business_type_data", type="object", example={"id": 1, "name": "Retail"}),
     *             @OA\Property(property="current_system_data", type="object", example={"id": 1, "name": "Manual"}),
     *             @OA\Property(property="lead_status_data", type="object", example={"id": 2, "name": "In Progress"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object", example={"shop_name": {"The shop name field is required."}})
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lead not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function update(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'shop_name' => 'sometimes|required|string|max:255',
            'contact_person' => 'sometimes|required|string|max:255',
            'mobile_number' => 'sometimes|required|string|max:20',
            'alternate_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'branches' => 'nullable|integer',
            'area_locality' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:10',
            'branches' => 'nullable|integer',
            'gps_location' => 'nullable|string|max:255',
            'business_type' => 'nullable|exists:business_types,id',
            'current_system' => 'nullable|exists:current_systems,id',
            'lead_status' => 'nullable|exists:lead_statuses,id',
            'plan_interest' => 'nullable|string|max:255',
            'next_follow_up_date' => 'nullable|date',
            'meeting_notes' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'action' => 'nullable|string|max:255',
        ]);

        // Update the last_updated_by
        $validated['last_updated_by'] = auth('sanctum')->id() ?? 1;

        // Check if status changed before updating
        $statusBeforeId = $lead->lead_status;
        $statusAfterId = $validated['lead_status'] ?? $statusBeforeId;

        // Update lead
        $lead->update($validated);

        // If status changed, log to LeadHistory
        if ($statusBeforeId !== $statusAfterId) {
            LeadHistory::create([
                'lead_id'       => $lead->id,
                'updated_by'    => auth('sanctum')->id() ?? 1,
                'action'        => $validated['action'] ?? 'Update',
                'status_before' => LeadStatus::where('id', $statusBeforeId)->value('name'),
                'status_after'  => LeadStatus::where('id', $statusAfterId)->value('name'),
                'timestamp'     => now(),
                'notes'         => $validated['meeting_notes'] ?? null,
            ]);
        }

        $lead->load([
            'createdByUser',
            'assignedToUser',
            'lastUpdatedByUser',
            'businessTypeData',
            'currentSystemData',
            'leadStatusData'
        ]);

        //Create an entry to LeadHistory with changes to status        
        return response()->json($lead);
    }

    /**
     * @OA\Delete(
     *     path="/api/leads/{lead}",
     *     summary="Delete lead",
     *     description="Delete a specific lead",
     *     operationId="deleteLead",
     *     tags={"Leads"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="lead",
     *         in="path",
     *         description="Lead ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lead deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lead deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lead not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function destroy(Lead $lead)
    {
        $lead->delete();

        return response()->json(['message' => 'Lead deleted successfully']);
    }

    /**
     * @OA\Delete(
     *     path="/api/leads-bulk",
     *     summary="Bulk delete leads",
     *     description="Delete multiple leads at once",
     *     operationId="bulkDeleteLeads",
     *     tags={"Leads"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"lead_ids"},
     *             @OA\Property(
     *                 property="lead_ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={1, 2, 3},
     *                 description="Array of lead IDs to delete"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Leads deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successfully deleted 3 lead(s)"),
     *             @OA\Property(property="deleted_count", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object", example={"lead_ids": {"The lead ids field is required."}})
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'exists:leads,id'
        ]);

        try {
            $deletedCount = Lead::whereIn('id', $request->lead_ids)->delete();
            
            return response()->json([
                'message' => "Successfully deleted {$deletedCount} lead(s)",
                'deleted_count' => $deletedCount
            ]);
        } catch (\Exception $e) {
            Log::error('Error bulk deleting leads: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete leads',
                'error' => app()->isProduction() ? null : $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/leads-form-options",
     *     summary="Get lead form options",
     *     description="Retrieve dropdown options for lead form (business types, current systems, lead statuses, users)",
     *     operationId="getLeadFormOptions",
     *     tags={"Leads"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Form options retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="business_types",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Retail")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="current_systems",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Manual")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="lead_statuses",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="New")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="users",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function getFormOptions()
    {
        return response()->json([
            'business_types' => BusinessType::all(),
            'current_systems' => CurrentSystem::all(),
            'lead_statuses' => LeadStatus::all(),
            'users' => User::select('id', 'name', 'email')->get(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/leads-export",
     *     summary="Export leads to CSV",
     *     description="Export leads to CSV file with all applied filters",
     *     operationId="exportLeads",
     *     tags={"Leads"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="shop_name",
     *         in="query",
     *         description="Filter by shop name, contact person, email, or mobile number",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="contact_person",
     *         in="query",
     *         description="Filter by contact person name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="mobile_number",
     *         in="query",
     *         description="Filter by mobile number",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="Filter by email address",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="area_locality",
     *         in="query",
     *         description="Filter by area or locality",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="business_type",
     *         in="query",
     *         description="Filter by business type ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="current_system",
     *         in="query",
     *         description="Filter by current system ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="lead_status",
     *         in="query",
     *         description="Filter by lead status ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="assigned_to",
     *         in="query",
     *         description="Filter by assigned user ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="created_by",
     *         in="query",
     *         description="Filter by creator user ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="next_follow_up_date",
     *         in="query",
     *         description="Filter by next follow-up date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Filter by creation date range start (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Filter by creation date range end (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="CSV file downloaded successfully",
     *         @OA\MediaType(
     *             mediaType="text/csv",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function export(Request $request)
    {
        $query = Lead::with([
            'createdByUser',
            'assignedToUser',
            'lastUpdatedByUser',
            'businessTypeData',
            'currentSystemData',
            'leadStatusData'
        ]);

        // Apply the same filters as the index method
        if ($request->filled('shop_name')) {
            $query->where(function ($q) use ($request) {
                $q->where('shop_name', 'like', '%' . $request->shop_name . '%')
                    ->orWhere('contact_person', 'like', '%' . $request->shop_name . '%')
                    ->orWhere('email', 'like', '%' . $request->shop_name . '%')
                    ->orWhere('mobile_number', 'like', '%' . $request->shop_name . '%');
            });
        }

        if ($request->filled('contact_person')) {
            $query->where('contact_person', 'like', '%' . $request->contact_person . '%');
        }

        if ($request->filled('mobile_number')) {
            $query->where('mobile_number', 'like', '%' . $request->mobile_number . '%');
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }

        if ($request->filled('area_locality')) {
            $query->where('area_locality', 'like', '%' . $request->area_locality . '%');
        }

        if ($request->filled('business_type')) {
            $query->where('business_type', $request->business_type);
        }

        if ($request->filled('current_system')) {
            $query->where('current_system', $request->current_system);
        }

        if ($request->filled('lead_status')) {
            $query->where('lead_status', $request->lead_status);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        if ($request->filled('next_follow_up_date')) {
            $query->whereDate('next_follow_up_date', $request->next_follow_up_date);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59',
            ]);
        }

        // Get all leads without pagination
        $leads = $query->orderBy('created_at', 'desc')->get();

        // Generate CSV content
        $csvData = [];
        
        // CSV Headers
        $csvData[] = [
            'ID',
            'Created Date',
            'Shop Name',
            'Contact Person',
            'Mobile Number',
            'Alternate Number',
            'Email',
            'Address',
            'Area/Locality',
            'Pincode',
            'Branches',
            'GPS Location',
            'Business Type',
            'Current System',
            'Lead Status',
            'Plan Interest',
            'Next Follow-up Date',
            'Meeting Notes',
            'Assigned To',
            'Created By',
            'Last Updated By',
            'Completed At',
            'Created At',
            'Updated At'
        ];

        // CSV Data rows
        foreach ($leads as $lead) {
            $csvData[] = [
                $lead->id,
                $lead->created_at ? (is_string($lead->created_at) ? $lead->created_at : $lead->created_at->format('Y-m-d H:i:s')) : '',
                $lead->shop_name,
                $lead->contact_person,
                $lead->mobile_number,
                $lead->alternate_number,
                $lead->email,
                $lead->address,
                $lead->area_locality,
                $lead->pincode,
                $lead->branches,
                $lead->gps_location,
                $lead->businessTypeData ? $lead->businessTypeData->name : '',
                $lead->currentSystemData ? $lead->currentSystemData->name : '',
                $lead->leadStatusData ? $lead->leadStatusData->name : '',
                $lead->plan_interest,
                $lead->next_follow_up_date ? (is_string($lead->next_follow_up_date) ? $lead->next_follow_up_date : $lead->next_follow_up_date->format('Y-m-d')) : '',
                $lead->meeting_notes,
                $lead->assignedToUser ? $lead->assignedToUser->name : '',
                $lead->createdByUser ? $lead->createdByUser->name : '',
                $lead->lastUpdatedByUser ? $lead->lastUpdatedByUser->name : '',
                $lead->completed_at ? (is_string($lead->completed_at) ? $lead->completed_at : $lead->completed_at->format('Y-m-d H:i:s')) : '',
                $lead->created_at ? (is_string($lead->created_at) ? $lead->created_at : $lead->created_at->format('Y-m-d H:i:s')) : '',
                $lead->updated_at ? (is_string($lead->updated_at) ? $lead->updated_at : $lead->updated_at->format('Y-m-d H:i:s')) : ''
            ];
        }

        // Convert to CSV string
        $csvContent = '';
        foreach ($csvData as $row) {
            $csvContent .= implode(',', array_map(function($field) {
                // Escape fields that contain commas, quotes, or newlines
                if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
                    return '"' . str_replace('"', '""', $field) . '"';
                }
                return $field;
            }, $row)) . "\n";
        }

        // Generate filename with timestamp
        $filename = 'leads_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        // Return CSV file download
        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }


}
