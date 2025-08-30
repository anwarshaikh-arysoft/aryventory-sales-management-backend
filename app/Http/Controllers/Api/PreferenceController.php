<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Preference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PreferenceController extends Controller
{
    /**
     * GET /api/preferences
     * List preferences (paginated).
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $query = Preference::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->get('q').'%');
            })
            ->orderBy('id');

        $paginated = $query->paginate($perPage);

        return response()->json($paginated);
    }

    /**
     * POST /api/preferences
     * Create a preference.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required','string','max:255','unique:preferences,name'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $preference = Preference::create($validator->validated());

        return response()->json([
            'message' => 'Preference created successfully.',
            'data'    => $preference,
        ], 201);
    }

    /**
     * GET /api/preferences/{preference}
     * Show a single preference.
     */
    public function show(Preference $preference)
    {
        return response()->json($preference);
    }

    /**
     * PUT/PATCH /api/preferences/{preference}
     * Update a preference.
     */
    public function update(Request $request, Preference $preference)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required','string','max:255','unique:preferences,name,'.$preference->id],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $preference->update($validator->validated());

        return response()->json([
            'message' => 'Preference updated successfully.',
            'data'    => $preference,
        ]);
    }

    /**
     * DELETE /api/preferences/{preference}
     * Delete a preference.
     */
    public function destroy(Preference $preference)
    {
        try {
            $preference->delete();

            return response()->json([
                'message' => 'Preference deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete preference. It may be in use.',
                'error' => $e->getMessage(),
            ], 409);
        }
    }
}
