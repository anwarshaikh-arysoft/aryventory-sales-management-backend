<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Preference;
use App\Models\UserPreference;
use Illuminate\Support\Facades\Log;

class UserPreferencesController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/user-preferences",
     *     summary="Get user preferences",
     *     description="Retrieve user preferences for the authenticated user",
     *     operationId="getUserPreferences",
     *     tags={"Settings"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User preferences retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User preferences retrieved successfully."),
     *             @OA\Property(
     *                 property="preferences",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="preference_id", type="integer", example=1),
     *                     @OA\Property(property="status", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
     *                     @OA\Property(property="preference", type="object", example={"id": 1, "name": "Email Notifications", "description": "Receive email notifications"})
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to fetch user preferences"),
     *             @OA\Property(property="error", type="string", example="Internal server error")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $preferences = UserPreference::
                where('user_id', $request->user()->id)
                ->with(['preference'])
                ->get();

            Log::info(" User preferences retrieved successfully.");
            return response()->json([
                'message' => 'User preferences retrieved successfully.',
                'preferences' => $preferences,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to fetch user preferences',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/user-preferences/update",
     *     summary="Update user preferences",
     *     description="Update user preferences for the authenticated user",
     *     operationId="updateUserPreferences",
     *     tags={"Settings"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"preferences"},
     *             @OA\Property(
     *                 property="preferences",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="preference_id", type="integer", example=1, description="Preference ID"),
     *                     @OA\Property(property="status", type="boolean", example=true, description="Preference status")
     *                 ),
     *                 example={{"preference_id": 1, "status": true}, {"preference_id": 2, "status": false}}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User preferences updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User preferences updated successfully."),
     *             @OA\Property(
     *                 property="preferences",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="preference_id", type="integer", example=1),
     *                     @OA\Property(property="status", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T10:00:00Z"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T10:00:00Z")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object", example={"preferences": {"The preferences field is required."}})
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to update user preferences"),
     *             @OA\Property(property="error", type="string", example="Internal server error")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function update(Request $request)
    {
        Log::info(" Update api hit for user preferences.");

        try {
            $user = $request->user();

            $validated = $request->validate([
                'preferences' => 'required|array',
                'preferences.*.preference_id' => 'required|exists:preferences,id',
                'preferences.*.status' => 'required|boolean',
            ]);

            foreach ($validated['preferences'] as $pref) {
                UserPreference::updateOrCreate(
                    ['user_id' => $user->id, 'preference_id' => $pref['preference_id']],
                    ['status' => $pref['status']]
                );
            }

            Log::info(" User preferences updated successfully.");
            return response()->json(['message' => 'User preferences updated successfully.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to update user preferences',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getPreferences()
    {
        try {
            $preferences = Preference::all();
            Log::info(" All preferences retrieved successfully.");
            return response()->json([
                'message' => 'All preferences retrieved successfully.',
                'preferences' => $preferences,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to fetch preferences',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUserPreferences(Request $request)
    {
        try {
            $userPreferences = UserPreference::where('user_id', $request->user()->id)
                ->with('preference')
                ->get();

            Log::info(" User preferences retrieved successfully.");
            return response()->json([
                'message' => 'User preferences retrieved successfully.',
                'preferences' => $userPreferences,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to fetch user preferences',
                'error' => $e->getMessage(),
            ], 500);
        }
    }   

}
