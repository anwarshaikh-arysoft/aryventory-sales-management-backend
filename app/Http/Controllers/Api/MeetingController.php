<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MeetingController extends Controller
{

    public function index()
    {
        return Meeting::with(['lead', 'recordedAudios', 'selfies', 'shopPhotos'])->get();
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lead_id'            => 'required|exists:leads,id',
            'meeting_start_time' => 'required|date',
            'meeting_end_time'   => 'nullable|date|after_or_equal:meeting_start_time',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $meeting = Meeting::create($validator->validated());
            return response()->json($meeting, 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to create meeting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Meeting $meeting)
    {
        return $meeting->load(['lead', 'recordedAudios', 'selfies', 'shopPhotos']);
    }

    public function update(Request $request, Meeting $meeting)
    {
        try {
            $validated = $request->validate([
                'lead_id' => 'sometimes|exists:leads,id',
                'meeting_start_time' => 'sometimes|date',
                'meeting_end_time' => 'nullable|date|after_or_equal:meeting_start_time',
            ]);

            $meeting->update($validated);
            return response()->json($meeting);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to update meeting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Meeting $meeting)
    {
        try {
            $meeting->delete();
            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to delete meeting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
