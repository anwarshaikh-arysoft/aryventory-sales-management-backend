<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShopPhotoForMeeting;
use Illuminate\Http\Request;

class ShopPhotoForMeetingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return ShopPhotoForMeeting::with('meeting')->get();
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

            $shopPhoto = ShopPhotoForMeeting::create($validated);
            return response()->json($shopPhoto, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to create shop photo', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ShopPhotoForMeeting $shopPhotoForMeeting)
    {
        return $shopPhotoForMeeting->load('meeting');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ShopPhotoForMeeting $shopPhotoForMeeting)
    {
        try {
            $validated = $request->validate([
                'meeting_id' => 'sometimes|exists:meetings,id',
                'media'      => 'sometimes|string|max:255',
            ]);

            $shopPhotoForMeeting->update($validated);
            return response()->json($shopPhotoForMeeting);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to update shop photo', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShopPhotoForMeeting $shopPhotoForMeeting)
    {
        try {
            $shopPhotoForMeeting->delete();
            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to delete shop photo', 'error' => $e->getMessage()], 500);
        }
    }
}
