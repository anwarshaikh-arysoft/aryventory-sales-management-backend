<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CurrentSystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CurrentSystemController extends Controller
{
    /**
     * GET /api/current-systems
     * List current systems (paginated).
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $query = CurrentSystem::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->get('q').'%');
            })
            ->orderBy('id');

        $paginated = $query->paginate($perPage);

        return response()->json($paginated);
    }

    /**
     * POST /api/current-systems
     * Create a current system.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required','string','max:255','unique:current_systems,name'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $currentSystem = CurrentSystem::create($validator->validated());

        return response()->json([
            'message' => 'Current system created successfully.',
            'data'    => $currentSystem,
        ], 201);
    }

    /**
     * GET /api/current-systems/{current_system}
     * Show a single current system.
     */
    public function show(CurrentSystem $currentSystem)
    {
        return response()->json($currentSystem);
    }

    /**
     * PUT/PATCH /api/current-systems/{current_system}
     * Update a current system.
     */
    public function update(Request $request, CurrentSystem $currentSystem)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required','string','max:255','unique:current_systems,name,'.$currentSystem->id],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $currentSystem->update($validator->validated());

        return response()->json([
            'message' => 'Current system updated successfully.',
            'data'    => $currentSystem,
        ]);
    }

    /**
     * DELETE /api/current-systems/{current_system}
     * Delete a current system.
     */
    public function destroy(CurrentSystem $currentSystem)
    {
        try {
            $currentSystem->delete();

            return response()->json([
                'message' => 'Current system deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete current system. It may be in use.',
                'error' => $e->getMessage(),
            ], 409);
        }
    }
}
