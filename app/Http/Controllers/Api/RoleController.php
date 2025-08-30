<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $query = Role::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->get('q').'%');
            })
            ->orderBy('id');

        $paginated = $query->paginate($perPage);

        return response()->json($paginated);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $role = Role::create($data);

        return response()->json($role, 201);
    }

    public function show($id)
    {
        try {
            $role = Role::findOrFail($id);
            return response()->json($role);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Role not found'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to fetch role', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $role = Role::findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $role->update($request->only('name'));

            return response()->json($role);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Role not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to update role', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $role = Role::findOrFail($id);
            $role->delete();

            return response()->json(['message' => 'Role deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Role not found'], 404);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to delete role', 'error' => $e->getMessage()], 500);
        }
    }
}

