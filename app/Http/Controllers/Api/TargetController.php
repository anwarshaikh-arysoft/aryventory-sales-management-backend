<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Target;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TargetController extends Controller
{
    /**
     * GET /api/targets
     * List targets (paginated).
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $query = Target::query()
            ->with('user')
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->whereHas('user', function ($subQ) use ($request) {
                    $subQ->where('name', 'like', '%'.$request->get('q').'%');
                });
            })
            ->orderBy('id');

        $paginated = $query->paginate($perPage);

        return response()->json($paginated);
    }

    /**
     * POST /api/targets
     * Create a target.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required','exists:users,id'],
            'daily_meeting_targets' => ['required','integer','min:0'],
            'closure_target' => ['required','integer','min:0'],
            'revenue_targets' => ['required','numeric','min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $target = Target::create($validator->validated());
        $target->load('user');

        return response()->json([
            'message' => 'Target created successfully.',
            'data'    => $target,
        ], 201);
    }

    /**
     * GET /api/targets/{target}
     * Show a single target.
     */
    public function show(Target $target)
    {
        $target->load('user');
        return response()->json($target);
    }

    /**
     * PUT/PATCH /api/targets/{target}
     * Update a target.
     */
    public function update(Request $request, Target $target)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => ['required','exists:users,id'],
            'daily_meeting_targets' => ['required','integer','min:0'],
            'closure_target' => ['required','integer','min:0'],
            'revenue_targets' => ['required','numeric','min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $target->update($validator->validated());
        $target->load('user');

        return response()->json([
            'message' => 'Target updated successfully.',
            'data'    => $target,
        ]);
    }

    /**
     * DELETE /api/targets/{target}
     * Delete a target.
     */
    public function destroy(Target $target)
    {
        try {
            $target->delete();

            return response()->json([
                'message' => 'Target deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete target.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
