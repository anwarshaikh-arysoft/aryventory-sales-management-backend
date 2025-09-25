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
     * Display a listing of leads.
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
     * Display the specified lead.
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
     * Update the specified lead in storage.
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
     * Remove the specified lead from storage.
     */
    public function destroy(Lead $lead)
    {
        $lead->delete();

        return response()->json(['message' => 'Lead deleted successfully']);
    }

    /**
     * Bulk delete leads
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
     * Get dropdown options for lead form
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
     * Export leads to CSV with all applied filters
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
