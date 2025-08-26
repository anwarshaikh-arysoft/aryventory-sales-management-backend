<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeadStatusController extends Controller
{
    /**
     * GET /api/lead-statuses
     * List lead statuses (paginated).
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $query = LeadStatus::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->get('q').'%');
            })
            ->orderBy('id');

        $paginated = $query->paginate($perPage);

        return response()->json($paginated);
    }

    /**
     * POST /api/lead-statuses
     * Create a lead status.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required','string','max:100','unique:lead_statuses,name'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $status = LeadStatus::create($validator->validated());

        return response()->json([
            'message' => 'Lead status created successfully.',
            'data'    => $status,
        ], 201);
    }

    /**
     * GET /api/lead-statuses/{lead_status}
     * Show a single lead status.
     */
    public function show(LeadStatus $lead_status)
    {
        return response()->json($lead_status);
    }

    /**
     * PUT/PATCH /api/lead-statuses/{lead_status}
     * Update a lead status.
     */
    public function update(Request $request, LeadStatus $lead_status)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required','string','max:100','unique:lead_statuses,name,'.$lead_status->id],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $lead_status->update($validator->validated());

        return response()->json([
            'message' => 'Lead status updated successfully.',
            'data'    => $lead_status,
        ]);
    }

    /**
     * DELETE /api/lead-statuses/{lead_status}
     * Delete a lead status.
     */
    public function destroy(LeadStatus $lead_status)
    {
        $lead_status->delete();

        return response()->json([
            'message' => 'Lead status deleted successfully.',
        ], 200);
    }
}
