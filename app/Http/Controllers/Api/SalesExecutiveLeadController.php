<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\LeadStatus;

class SalesExecutiveLeadController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/sales-executive/leads",
     *     summary="Get sales executive leads",
     *     description="Retrieve all leads assigned to the logged-in sales executive",
     *     operationId="getSalesExecutiveLeads",
     *     tags={"Leads"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Sales executive leads retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="shop_name", type="string", example="ABC Electronics"),
     *                 @OA\Property(property="contact_person", type="string", example="John Doe"),
     *                 @OA\Property(property="mobile_number", type="string", example="9876543210"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="address", type="string", example="123 Main Street, City"),
     *                 @OA\Property(property="area_locality", type="string", example="Downtown"),
     *                 @OA\Property(property="pincode", type="string", example="123456"),
     *                 @OA\Property(property="business_type", type="integer", example=1),
     *                 @OA\Property(property="current_system", type="integer", example=1),
     *                 @OA\Property(property="lead_status", type="integer", example=1),
     *                 @OA\Property(property="plan_interest", type="string", example="Premium Plan"),
     *                 @OA\Property(property="next_follow_up_date", type="string", format="date", example="2024-01-15"),
     *                 @OA\Property(property="meeting_notes", type="string", example="Initial discussion completed"),
     *                 @OA\Property(property="assigned_to", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
     *                 @OA\Property(property="lead_status_data", type="object", example={"id": 1, "name": "New"})
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index()
    {
        // get all leads assigned to the logged-in sales executive
        $leads = Lead::where('assigned_to', Auth::id())
            ->with('leadStatusData')
            ->get();
        return response()->json($leads);
    }

    /**
     * @OA\Get(
     *     path="/api/sales-executive/lead-counts-by-status",
     *     summary="Get lead counts by status",
     *     description="Get lead counts grouped by status for the logged-in sales executive",
     *     operationId="getLeadCountsByStatus",
     *     tags={"Leads"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lead counts by status retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="lead_status", type="integer", example=1),
     *                 @OA\Property(property="status_name", type="string", example="New"),
     *                 @OA\Property(property="total", type="integer", example=5)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function leadCountsByStatus()
    {
        $userId = Auth::id();
        $counts = Lead::where('assigned_to', $userId)
            ->select('lead_status', \DB::raw('count(*) as total'))
            ->groupBy('lead_status')
            ->with('leadStatusData:id,name') // Eager load lead status details
            ->get()
            ->map(function ($item) {
                return [
                    'lead_status' => $item->lead_status,
                    'status_name' => $item->leadStatusData ? $item->leadStatusData->name : null,
                    'total' => $item->total,
                ];
            });
        return response()->json($counts);
    }

    /**
     * @OA\Get(
     *     path="/api/sales-executive/leads-advanced",
     *     summary="Get sales executive leads with advanced filtering",
     *     description="Retrieve all leads assigned to the authenticated sales executive with advanced filtering, search, and pagination",
     *     operationId="getSalesExecutiveLeadsAdvanced",
     *     tags={"Leads"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by shop name, contact person, mobile number, email, address, area locality, or pincode",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="lead_status",
     *         in="query",
     *         description="Filter by lead status (can be array or single value, or 'today' for today's follow-ups, or 'sold' for sold leads)",
     *         required=false,
     *         @OA\Schema(
     *             oneOf={
     *                 @OA\Schema(type="array", @OA\Items(type="integer")),
     *                 @OA\Schema(type="integer"),
     *                 @OA\Schema(type="string", enum={"today", "sold"})
     *             }
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="follow_up_start_date",
     *         in="query",
     *         description="Filter by follow-up date range start (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="follow_up_end_date",
     *         in="query",
     *         description="Filter by follow-up date range end (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort field",
     *         required=false,
     *         @OA\Schema(type="string", enum={"next_follow_up_date", "created_at", "updated_at", "shop_name"}, default="next_follow_up_date")
     *     ),
     *     @OA\Parameter(
     *         name="direction",
     *         in="query",
     *         description="Sort direction",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="asc")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
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
    public function leads(Request $request)
    {
        $query = Lead::query()
            ->where('assigned_to', Auth::id())
            ->with('leadStatusData');

        // Keyword search
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('shop_name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('mobile_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('area_locality', 'like', "%{$search}%")
                    ->orWhere('pincode', 'like', "%{$search}%");
            });
        }

        // Resolve important status IDs once
        $soldStatusId = LeadStatus::where('name', 'Sold')->value('id');
        $noFollowUpStatuses = LeadStatus::whereIn('name', [
            'Sold',
            'Already Using CRM',
            'Not Interested',
            'Using Different App'
        ])->pluck('id');

        $leadStatusInput = $request->input('lead_status');
        $isSold = false;

        // "today" bucket (string control)
        if ($leadStatusInput === 'today') {
            $query->whereDate('next_follow_up_date', '=', Carbon::today())
                ->whereNotIn('lead_status', $noFollowUpStatuses);
        } else {
            // Normalize to array for easier checks (but keep original for exact-sold detection)
            $statuses = is_array($leadStatusInput) ? $leadStatusInput : ($leadStatusInput !== null ? [$leadStatusInput] : []);

            // Detect Sold: string "sold", numeric equals soldStatusId, or a single-item array equal to soldStatusId
            $isExactlySold =
                $leadStatusInput === 'sold' ||
                (is_numeric($leadStatusInput) && (int)$leadStatusInput === (int)$soldStatusId) ||
                (is_array($leadStatusInput) && count($leadStatusInput) === 1 && (int)$leadStatusInput[0] === (int)$soldStatusId);

            if ($isExactlySold) {
                $isSold = true;
                $query->where('lead_status', $soldStatusId)
                    ->orderBy('completed_at', 'desc'); // special sort for Sold
            } elseif (!empty($statuses)) {
                // Regular status filtering (no special sort)
                $query->whereIn('lead_status', $statuses);
            }
        }

        // Follow-up date range
        if ($start = $request->input('follow_up_start_date')) {
            $query->whereDate('next_follow_up_date', '>=', date('Y-m-d', strtotime($start)));
        }
        if ($end = $request->input('follow_up_end_date')) {
            $query->whereDate('next_follow_up_date', '<=', date('Y-m-d', strtotime($end)));
        }

        // Apply global sort ONLY if not Sold
        if (!$isSold) {
            $allowedSorts = ['next_follow_up_date', 'created_at', 'updated_at', 'shop_name'];
            $sort = $request->input('sort', 'next_follow_up_date');
            $dir = strtolower($request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
            if (!in_array($sort, $allowedSorts, true)) {
                $sort = 'next_follow_up_date';
            }
            $query->orderBy($sort, $dir);
        }

        // Pagination
        $perPage = (int) $request->get('per_page', 15);
        if ($perPage <= 0) $perPage = 15;

        $leads = $query->paginate($perPage)->appends($request->query());

        return response()->json($leads);
    }




    /**
     * Store a new lead assigned to the logged-in sales executive.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'shop_name' => 'required|string|max:255',
                'contact_person' => 'required|string|max:255',
                'mobile_number' => 'required|string|max:20',
                'email' => 'nullable|email',
                'address' => 'nullable|string',
                'branches' => 'nullable|integer',
                'area_locality' => 'nullable|string',
                'pincode' => 'nullable|string',
                'gps_location' => 'nullable|string',
                'business_type' => 'nullable|integer',
                'current_system' => 'nullable|integer',
                'lead_status' => 'nullable|integer',
                'plan_interest' => 'nullable|string',
                'next_follow_up_date' => 'nullable|date',
                'meeting_notes' => 'nullable|string',
            ]);

            Log::info($validated);

            $validated['created_by'] = Auth::id();
            $validated['assigned_to'] = Auth::id();
            $validated['last_updated_by'] = Auth::id();

            $lead = Lead::create($validated);

            return response()->json([
                'success' => true,
                'data' => $lead
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a specific lead belonging to the logged-in sales executive.
     */
    public function show(Lead $lead)
    {
        $this->authorizeLead($lead);
        return response()->json($lead);
    }

    /**
     * Update a specific lead.
     */
    public function update(Request $request, Lead $lead)
    {
        $this->authorizeLead($lead);

        $validated = $request->validate([
            'shop_name' => 'sometimes|string|max:255',
            'contact_person' => 'sometimes|string|max:255',
            'mobile_number' => 'sometimes|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'area_locality' => 'nullable|string',
            'pincode' => 'nullable|string',
            'branches' => 'nullable|integer',
            'gps_location' => 'nullable|string',
            'business_type' => 'nullable|integer',
            'current_system' => 'nullable|integer',
            'lead_status' => 'nullable|integer',
            'plan_interest' => 'nullable|string',
            'next_follow_up_date' => 'nullable|date',
            'meeting_notes' => 'nullable|string',
            'completed_at' => 'nullable|date',
        ]);

        $validated['last_updated_by'] = Auth::id();
        $lead->update($validated);

        return response()->json($lead);
    }

    /**
     * Remove a specific lead.
     */
    public function destroy(Lead $lead)
    {
        $this->authorizeLead($lead);
        $lead->delete();

        return response()->json(null, 204);
    }

    /**
     * Get leads sorted by next_follow_up_date.
     */
    // public function leadsByFollowUpDate(Request $request)
    // {
    //     $perPage = $request->get('per_page', 15); // Default 15 per page if not provided

    //     $noFollowUpStatuses = LeadStatus::whereIn('name', ['Sold', 'Already Using CRM', 'Not Interested', 'Using Different App'])->pluck('id');

    //     $totalLeads = Lead::where('assigned_to', Auth::id())
    //         ->whereNotIn('lead_status', $noFollowUpStatuses)
    //         ->count();

    //     $leads = Lead::where('assigned_to', Auth::id())
    //         ->with('leadStatusData')
    //         ->whereNotIn('lead_status', $noFollowUpStatuses)
    //         ->orderBy('next_follow_up_date', 'asc')
    //         ->paginate($perPage);

    //     return response()->json([
    //         'leads' => $leads,
    //         'total_leads' => $totalLeads
    //     ]);
    // }

    public function leadsByFollowUpDate(Request $request)
    {
        $perPage = $request->get('per_page', 15); // Default 15 per page if not provided

        $noFollowUpStatuses = LeadStatus::whereIn('name', ['Sold', 'Already Using CRM', 'Not Interested', 'Using Different App'])
            ->pluck('id');

        $totalLeads = Lead::where('assigned_to', Auth::id())
            ->where(function ($query) use ($noFollowUpStatuses) {
                $query->whereNotIn('lead_status', $noFollowUpStatuses)
                    ->orWhereNull('lead_status');
            })
            ->count();

        $leads = Lead::where('assigned_to', Auth::id())
            ->with('leadStatusData')
            ->where(function ($query) use ($noFollowUpStatuses) {
                $query->whereNotIn('lead_status', $noFollowUpStatuses)
                    ->orWhereNull('lead_status');
            })
            ->orderBy('next_follow_up_date', 'asc')
            ->paginate($perPage);

        return response()->json([
            'leads' => $leads,
            'total_leads' => $totalLeads
        ]);
    }

    /**
     * Ensure lead belongs to logged-in sales executive.
     */
    protected function authorizeLead(Lead $lead)
    {
        if ($lead->assigned_to !== Auth::id()) {
            abort(403, 'Unauthorized');
        }
    }
}
