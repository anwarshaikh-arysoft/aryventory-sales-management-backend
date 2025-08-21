<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SelfieForMeeting;
use Illuminate\Http\Request;

class SelfieForMeetingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return SelfieForMeeting::with('meeting')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'meeting_id' => 'required|exists:meetings,id',
                'media'      => 'required|string|max:255',
            ]);

            $selfie = SelfieForMeeting::create($validated);
            return response()->json($selfie, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to create selfie', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SelfieForMeeting $selfieForMeeting)
    {
        return $selfieForMeeting->load('meeting');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SelfieForMeeting $selfieForMeeting)
    {
        try {
            $validated = $request->validate([
                'meeting_id' => 'sometimes|exists:meetings,id',
                'media'      => 'sometimes|string|max:255',
            ]);

            $selfieForMeeting->update($validated);
            return response()->json($selfieForMeeting);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to update selfie', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SelfieForMeeting $selfieForMeeting)
    {
        try {
            $selfieForMeeting->delete();
            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to delete selfie', 'error' => $e->getMessage()], 500);
        }
    }
}
