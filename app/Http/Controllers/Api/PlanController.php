<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PlanController extends Controller
{
    /**
     * GET /api/plans
     * List plans (paginated).
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $query = Plan::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->get('q').'%');
            })
            ->orderBy('id');

        $paginated = $query->paginate($perPage);

        return response()->json($paginated);
    }

    /**
     * POST /api/plans
     * Create a plan.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required','string','max:255'],
            'interval' => ['required','string','max:50'],
            'amount' => ['required','numeric','min:0'],
            'status' => ['required','string','max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $plan = Plan::create($validator->validated());

        return response()->json([
            'message' => 'Plan created successfully.',
            'data'    => $plan,
        ], 201);
    }

    /**
     * GET /api/plans/{plan}
     * Show a single plan.
     */
    public function show(Plan $plan)
    {
        return response()->json($plan);
    }

    /**
     * PUT/PATCH /api/plans/{plan}
     * Update a plan.
     */
    public function update(Request $request, Plan $plan)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required','string','max:255'],
            'interval' => ['required','string','max:50'],
            'amount' => ['required','numeric','min:0'],
            'status' => ['required','string','max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $plan->update($validator->validated());

        return response()->json([
            'message' => 'Plan updated successfully.',
            'data'    => $plan,
        ]);
    }

    /**
     * DELETE /api/plans/{plan}
     * Delete a plan.
     */
    public function destroy(Plan $plan)
    {
        try {
            $plan->delete();

            return response()->json([
                'message' => 'Plan deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete plan. It may be in use.',
                'error' => $e->getMessage(),
            ], 409);
        }
    }
}
