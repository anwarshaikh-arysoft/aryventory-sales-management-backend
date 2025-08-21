<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Preference;
use App\Models\UserPreference;
use Illuminate\Support\Facades\Log;

class UserPreferencesController extends Controller
{
    //
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
