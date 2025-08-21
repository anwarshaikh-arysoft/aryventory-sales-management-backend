<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecordedAudioForMeeting;
use Illuminate\Http\Request;

class RecordedAudioForMeetingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return RecordedAudioForMeeting::with('meeting')->get();
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

            $audio = RecordedAudioForMeeting::create($validated);
            return response()->json($audio, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to create recorded audio', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(RecordedAudioForMeeting $recordedAudioForMeeting)
    {
        return $recordedAudioForMeeting->load('meeting');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RecordedAudioForMeeting $recordedAudioForMeeting)
    {
        try {
            $validated = $request->validate([
                'meeting_id' => 'sometimes|exists:meetings,id',
                'media'      => 'sometimes|string|max:255',
            ]);

            $recordedAudioForMeeting->update($validated);
            return response()->json($recordedAudioForMeeting);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to update recorded audio', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RecordedAudioForMeeting $recordedAudioForMeeting)
    {
        try {
            $recordedAudioForMeeting->delete();
            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to delete recorded audio', 'error' => $e->getMessage()], 500);
        }
    }
}
