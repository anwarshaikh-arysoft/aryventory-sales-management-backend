<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
    */
    public function index(Request $request)
    {
        $query = User::with(['role', 'group']);

        // Filtering
        if ($request->filled('name')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->name . '%')
                  ->orWhere('email', 'like', '%' . $request->name . '%')
                  ->orWhere('phone', 'like', '%' . $request->name . '%')
                  ->orWhere('designation', 'like', '%' . $request->name . '%');
            });
        }

        if ($request->filled('email')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->email . '%')
                  ->orWhere('email', 'like', '%' . $request->email . '%')
                  ->orWhere('phone', 'like', '%' . $request->email . '%')
                  ->orWhere('designation', 'like', '%' . $request->email . '%');
            });
        }

        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }

        if ($request->filled('designation')) {
            $query->where('designation', 'like', '%' . $request->designation . '%');
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }

        // Pagination (default: 10 per page)
        $perPage = $request->integer('per_page', 10);
        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'        => 'required|string|max:255',
                'email'       => 'required|email|unique:users,email',
                'password'    => 'required|string|min:6',
                'phone'       => 'nullable|string|max:20',
                'designation' => 'nullable|string|max:255',
                'role_id'     => 'nullable|exists:roles,id',
                'group_id'    => 'nullable|exists:groups,id',
            ]);

            $validated['password'] = bcrypt($validated['password']);

            $user = User::create($validated);

            return response()->json($user, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to create user', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load(['role', 'group']);

        return response()->json($user);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'name'        => 'sometimes|required|string|max:255',
                'email'       => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
                'password'    => 'nullable|string|min:6',
                'phone'       => 'nullable|string|max:20',
                'designation' => 'nullable|string|max:255',
                'role_id'     => 'nullable|exists:roles,id',
                'group_id'    => 'nullable|exists:groups,id',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            }

            $user->update($validated);

            return response()->json($user);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to update user', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        try {
            $user->delete();
            return response()->json(['message' => 'User deleted successfully']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to delete user', 'error' => $e->getMessage()], 500);
        }
    }
}
