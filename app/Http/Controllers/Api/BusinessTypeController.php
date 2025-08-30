<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BusinessTypeController extends Controller
{
    /**
     * GET /api/business-types
     * List business types (paginated).
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $query = BusinessType::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->get('q').'%');
            })
            ->orderBy('id');

        $paginated = $query->paginate($perPage);

        return response()->json($paginated);
    }

    /**
     * POST /api/business-types
     * Create a business type.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required','string','max:255','unique:business_types,name'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $businessType = BusinessType::create($validator->validated());

        return response()->json([
            'message' => 'Business type created successfully.',
            'data'    => $businessType,
        ], 201);
    }

    /**
     * GET /api/business-types/{business_type}
     * Show a single business type.
     */
    public function show(BusinessType $businessType)
    {
        return response()->json($businessType);
    }

    /**
     * PUT/PATCH /api/business-types/{business_type}
     * Update a business type.
     */
    public function update(Request $request, BusinessType $businessType)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required','string','max:255','unique:business_types,name,'.$businessType->id],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $businessType->update($validator->validated());

        return response()->json([
            'message' => 'Business type updated successfully.',
            'data'    => $businessType,
        ]);
    }

    /**
     * DELETE /api/business-types/{business_type}
     * Delete a business type.
     */
    public function destroy(BusinessType $businessType)
    {
        try {
            $businessType->delete();

            return response()->json([
                'message' => 'Business type deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete business type. It may be in use.',
                'error' => $e->getMessage(),
            ], 409);
        }
    }
}
