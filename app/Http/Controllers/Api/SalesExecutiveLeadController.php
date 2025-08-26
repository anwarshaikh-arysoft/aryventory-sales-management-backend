<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SalesExecutiveLeadController extends Controller
{
    /**
     * Display a listing of the logged-in sales executive's leads.
     */
    public function index()
    {
        // get all leads assigned to the logged-in sales executive
        $leads = Lead::where('assigned_to', Auth::id())
            ->with('leadStatusData')
            ->get();
        return response()->json($leads);
    }

    // Ge the lead counts by status for the logged-in sales executive
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

    // get all leads assigned to the logged-in sales executive with pagination, search, and filters
    /* 
    GET /sales-executive/leads?search=sharma&lead_status[]=Open&lead_status[]=Warm&follow_up_start_date=2025-08-01&follow_up_end_date=2025-08-31&sort=next_follow_up_date&direction=asc&per_page=20
    */
    public function leads(Request $request)
    {
        $query = Lead::query()
            ->where('assigned_to', Auth::id())
            ->with('leadStatusData');

        // Keyword search across multiple columns
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

        // Filter by lead_status (supports single value or array of values)
        if ($request->filled('lead_status')) {
            $statuses = $request->input('lead_status');
            $statuses = is_array($statuses) ? $statuses : [$statuses];
            $query->whereIn('lead_status', $statuses);
        }

        // Filter by next_follow_up_date range (either start, end, or both)
        $start = $request->input('follow_up_start_date'); // e.g. '2025-08-01'
        $end   = $request->input('follow_up_end_date');   // e.g. '2025-08-31'

        if ($start) {
            $query->whereDate('next_follow_up_date', '>=', date('Y-m-d', strtotime($start)));
        }
        
        if ($end) {
            $query->whereDate('next_follow_up_date', '<=', date('Y-m-d', strtotime($end)));
        }

        // Sort + paginate (default: next_follow_up_date asc)
        $allowedSorts = ['next_follow_up_date', 'created_at', 'updated_at', 'shop_name'];
        $sort = $request->input('sort', 'next_follow_up_date');
        $dir  = strtolower($request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'next_follow_up_date';
        }
        $query->orderBy($sort, $dir);

        $perPage = (int) $request->get('per_page', 15);
        if ($perPage <= 0) {
            $perPage = 15;
        }

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
    public function leadsByFollowUpDate(Request $request)
    {
        $perPage = $request->get('per_page', 15); // Default 15 per page if not provided

        $leads = Lead::where('assigned_to', Auth::id())
            ->with('leadStatusData')
            ->orderBy('next_follow_up_date', 'asc')
            ->paginate($perPage);

        return response()->json($leads);
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
