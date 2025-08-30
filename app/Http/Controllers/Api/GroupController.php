<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $query = Group::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->get('q').'%');
            })
            ->orderBy('id');

        $paginated = $query->paginate($perPage);

        return response()->json($paginated);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $group = Group::create($data);

        return response()->json($group, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $group = Group::findOrFail($id);
            return response()->json($group);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Group not found'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to fetch group', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $group = Group::findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $group->update($request->only('name'));

            return response()->json($group);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Group not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to update group', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $group = Group::findOrFail($id);
            $group->delete();

            return response()->json(['message' => 'Group deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Group not found'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to delete group', 'error' => $e->getMessage()], 500);
        }
    }
}
