<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\LeadHistory;
use App\Models\SelfieForMeeting;
use App\Models\ShopPhotoForMeeting;
use App\Models\RecordedAudioForMeeting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Services\ObjectUploadService;

class MeetingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/meetings",
     *     summary="Get meetings list",
     *     description="Retrieve all meetings with related data",
     *     operationId="getMeetingsList",
     *     tags={"Meetings"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Meetings retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="lead_id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="meeting_start_time", type="string", format="date-time", example="2024-01-15T10:00:00Z"),
     *                 @OA\Property(property="meeting_end_time", type="string", format="date-time", example="2024-01-15T11:00:00Z", nullable=true),
     *                 @OA\Property(property="meeting_start_latitude", type="number", format="float", example=28.6139, nullable=true),
     *                 @OA\Property(property="meeting_start_longitude", type="number", format="float", example=77.2090, nullable=true),
     *                 @OA\Property(property="meeting_end_latitude", type="number", format="float", example=28.6140, nullable=true),
     *                 @OA\Property(property="meeting_end_longitude", type="number", format="float", example=77.2091, nullable=true),
     *                 @OA\Property(property="meeting_end_notes", type="string", example="Meeting completed successfully", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:00:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T11:00:00Z"),
     *                 @OA\Property(property="lead", type="object", description="Lead information"),
     *                 @OA\Property(property="recorded_audios", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="selfies", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="shop_photos", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index()
    {
        return Meeting::with(['lead', 'recordedAudios', 'selfies', 'shopPhotos'])->get();
    }

    /**
     * @OA\Post(
     *     path="/api/meetings",
     *     summary="Create a new meeting",
     *     description="Create a new meeting for a lead",
     *     operationId="createMeeting",
     *     tags={"Meetings"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"lead_id", "meeting_start_time"},
     *             @OA\Property(property="lead_id", type="integer", example=1, description="Lead ID"),
     *             @OA\Property(property="meeting_start_time", type="string", format="date-time", example="2024-01-15T10:00:00Z", description="Meeting start time"),
     *             @OA\Property(property="meeting_end_time", type="string", format="date-time", example="2024-01-15T11:00:00Z", description="Meeting end time", nullable=true),
     *             @OA\Property(property="meeting_start_latitude", type="number", format="float", example=28.6139, description="Start location latitude", nullable=true),
     *             @OA\Property(property="meeting_start_longitude", type="number", format="float", example=77.2090, description="Start location longitude", nullable=true),
     *             @OA\Property(property="meeting_end_latitude", type="number", format="float", example=28.6140, description="End location latitude", nullable=true),
     *             @OA\Property(property="meeting_end_longitude", type="number", format="float", example=77.2091, description="End location longitude", nullable=true),
     *             @OA\Property(property="meeting_end_notes", type="string", example="Meeting completed successfully", description="Meeting notes", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Meeting created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="lead_id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="meeting_start_time", type="string", format="date-time", example="2024-01-15T10:00:00Z"),
     *             @OA\Property(property="meeting_end_time", type="string", format="date-time", example="2024-01-15T11:00:00Z", nullable=true),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:00:00Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:00:00Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object", example={"lead_id": {"The lead id field is required."}})
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function store(Request $request)
    {

        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'lead_id'            => 'required|exists:leads,id',
            'meeting_start_time' => 'required|date',
            'meeting_end_time'   => 'nullable|date|after_or_equal:meeting_start_time',
            'user_id'            => $user->id,
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

    public function getMeetingStatus(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|exists:leads,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $meeting = Meeting::where('lead_id', $request->lead_id)->where('meeting_end_time', null)->first();
        return response()->json(['exists' => $meeting ? true : false, 'data' => $meeting]);
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

    /**
     * POST /meetings/start
     */
    /**
     * @OA\Post(
     *     path="/api/meetings/start",
     *     summary="Start a meeting",
     *     description="Start a meeting with selfie and location tracking",
     *     operationId="startMeeting",
     *     tags={"Meetings"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"lead_id"},
     *                 @OA\Property(property="lead_id", type="integer", example=1, description="Lead ID"),
     *                 @OA\Property(property="selfie", type="string", format="binary", description="Selfie image", nullable=true),
     *                 @OA\Property(property="latitude", type="number", format="float", example=28.6139, description="Location latitude", nullable=true),
     *                 @OA\Property(property="longitude", type="number", format="float", example=77.2090, description="Location longitude", nullable=true),
     *                 @OA\Property(property="address", type="string", example="123 Main Street, City", description="Location address", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Meeting started successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Meeting started successfully"),
     *             @OA\Property(property="meeting", type="object", description="Meeting details"),
     *             @OA\Property(property="selfie_url", type="string", example="https://s3.amazonaws.com/bucket/selfie.jpg", description="Selfie image URL", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object", example={"lead_id": {"The lead id field is required."}})
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function startMeeting(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'selfie'    => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'lead_id'   => ['required', 'integer'],
        ]);

        // check if meeting already exists
        $meeting = Meeting::where('lead_id', $request->lead_id)->where('meeting_end_time', null)->first();
        if ($meeting) {
            return response()->json(['message' => 'Meeting already exists'], 422);
        }

        // Upload selfie to S3 using ObjectUploadService
        $uploader = new ObjectUploadService();

        try {
            $upload = $uploader->upload(
                baseDirectory: 'shopphotos',
                file: $request->file('selfie'),
                userId: $user->id,
                prefix: 'meeting_start',
                options: [
                    'disk' => 's3',
                    'visibility' => 'private',             // signed URL returned
                    'add_date_path' => true,
                    'append_user_path' => true,
                    'signed_ttl' => 10,
                    // 'metadata' => ['lead_id' => (string)$data['lead_id']],
                ]
            );
        } catch (\Throwable $e) {
            Log::error('startMeeting: upload failed', ['error' => $e->getMessage()]);
            // return response()->json(['message' => 'Failed to upload selfie'], 500);
            $path = null;
            $url = null;
        }

        // Create new meeting
        $meeting = new Meeting();
        $meeting->lead_id = $request->lead_id;
        $meeting->user_id = $user->id;
        $meeting->meeting_start_time = now();
        $meeting->meeting_start_latitude = $data['latitude'];
        $meeting->meeting_start_longitude = $data['longitude'];
        $meeting->save();

        // Save start photo as a SHOP photo (store the S3 key)
        try {
            $path = $upload['key']; // S3 object key
            $url = $upload['url'];   // Signed URL
        } catch (\Throwable $e) {
            Log::error('startMeeting: upload failed', ['error' => $e->getMessage()]);
            $path = null;
            $url = null;
        }

        try {
        $meeting->shopPhotos()->create([
                'media' => $path, // store S3 object key, not a public path
            ]);
        } catch (\Throwable $e) {
            Log::error('startMeeting: shop photo save failed', ['error' => $e->getMessage()]);
            $path = null;
            $url = null;
        }

        return response()->json([
            'message'    => 'Meeting started successfully.',
            'meeting_id' => $meeting->id,
            'shop_photo' => $path,
            'selfie_url'  => $url,
        ]);
    }

    /**
     * POST /meetings/end
     */
    /**
     * @OA\Post(
     *     path="/api/meetings/end",
     *     summary="End a meeting",
     *     description="End a meeting with selfie and location tracking",
     *     operationId="endMeeting",
     *     tags={"Meetings"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"meeting_id"},
     *                 @OA\Property(property="meeting_id", type="integer", example=1, description="Meeting ID"),
     *                 @OA\Property(property="selfie", type="string", format="binary", description="End meeting selfie image", nullable=true),
     *                 @OA\Property(property="latitude", type="number", format="float", example=28.6140, description="End location latitude", nullable=true),
     *                 @OA\Property(property="longitude", type="number", format="float", example=77.2091, description="End location longitude", nullable=true),
     *                 @OA\Property(property="notes", type="string", example="Meeting completed successfully", description="Meeting notes", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Meeting ended successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Meeting ended successfully"),
     *             @OA\Property(property="meeting", type="object", description="Updated meeting details"),
     *             @OA\Property(property="selfie_url", type="string", example="https://s3.amazonaws.com/bucket/end_selfie.jpg", description="End selfie image URL", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object", example={"meeting_id": {"The meeting id field is required."}})
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Meeting not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function endMeeting(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'selfie'    => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'lead_id'   => ['required', 'integer'],
            'lead_status_id' => ['required', 'integer'],
            'plan_interest' => ['nullable', 'string'],
            // Add 3pg support        
            'recording' => ['nullable', 'file', 'mimes:mp3,3gp,mp4,aac,m4a,wav,ogg', 'max:30720'],
            // 'recording' => ['required','file'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'next_follow_up_date' => ['nullable', 'date'],
        ]);

        Log::info($data);

        // check if meeting already exists
        $meeting = Meeting::where('lead_id', $request->lead_id)->where('meeting_end_time', null)->first();
        if (!$meeting) {
            return response()->json(['message' => 'Meeting not found'], 422);
        }

        // Upload selfie to S3 using ObjectUploadService
        $uploader = new ObjectUploadService();

        try {
            $upload = $uploader->upload(
                baseDirectory: 'selfies',                 // keep consistent with shifts
                file: $request->file('selfie'),
                userId: $user->id,
                prefix: 'meeting_end',
                options: [
                    'disk' => 's3',
                    'visibility' => 'private',             // signed URL returned
                    'add_date_path' => true,
                    'append_user_path' => true,
                    'signed_ttl' => 10,
                    // 'metadata' => ['lead_id' => (string)$data['lead_id']],
                ]
            );
        } catch (\Throwable $e) {
            Log::error('startMeeting: upload failed', ['error' => $e->getMessage()]);
            // return response()->json(['message' => 'Failed to upload selfie'], 500);
            $path = null;
            $url = null;
        }

        // Update meeting end time
        $meeting->meeting_end_time = now();
        $meeting->meeting_end_latitude = $data['latitude'];
        $meeting->meeting_end_longitude = $data['longitude'];
        $meeting->meeting_end_notes = $data['notes'];
        $meeting->save();

        // Save end selfie as a SELFIE
        try {
            $path = $upload['key']; // S3 object key
            $url = $upload['url'];   // Signed URL
        } catch (\Throwable $e) {
            Log::error('startMeeting: upload failed', ['error' => $e->getMessage()]);
            $path = null;
            $url = null;
        }

        try {
        $meeting->selfies()->create([
            'media' => $path,
            ]);
        } catch (\Throwable $e) {
            Log::error('startMeeting: selfie save failed', ['error' => $e->getMessage()]);
            $path = null;
            $url = null;
        }

        // Upload recording to S3 using ObjectUploadService
        $recordingUploader = new ObjectUploadService();

        try {
            $recordingUpload = $recordingUploader->upload(
                baseDirectory: 'recordings',
                file: $request->file('recording'),
                userId: $user->id,
                prefix: 'voice',
                options: [
                    'disk' => 's3',
                    'visibility' => 'private',
                    'add_date_path' => true,
                    'append_user_path' => true,
                    'signed_ttl' => 10,
                    // 'metadata' => ['lead_id' => (string)$data['lead_id']],
                ]
            );
        } catch (\Throwable $e) {
            Log::error('endMeeting: recording upload failed', ['error' => $e->getMessage()]);
            // return response()->json(['message' => 'Failed to upload recording'], 500);
            $recPath = null;
            $recUrl = null;
        }

        try {
        // Save recording as RECORDED AUDIO
            $recPath = $recordingUpload['key']; // S3 object key
            $recUrl = $recordingUpload['url'];   // Signed URL
        } catch (\Throwable $e) {
            Log::error('startMeeting: recording upload failed', ['error' => $e->getMessage()]);
            $recPath = null;
            $recUrl = null;
        }

        try {
        $meeting->recordedAudios()->create([
                'media' => $recPath,
            ]);
        } catch (\Throwable $e) {
            Log::error('endMeeting: recording save failed', ['error' => $e->getMessage()]);
            // return response()->json(['message' => 'Failed to save recording'], 500);
            $recPath = null;
            $recUrl = null;
        }


        // Current Lead Status
        $lead = Lead::find($request->lead_id);
        $leadStatusId = $lead->lead_status;

        try {
            $currentLeadStatus = LeadStatus::find($leadStatusId)->name;
        } catch (\Throwable $e) {
            Log::error('endMeeting: current lead status not found', ['error' => $e->getMessage()]);
            $currentLeadStatus = 'Unknown';
        }

        $newLeadStatus = LeadStatus::find($data['lead_status_id'])->name;
        
        // update lead status as per the lead_status_id
        $lead = Lead::find($request->lead_id);
        $lead->lead_status = $data['lead_status_id'];
        $lead->plan_interest = $data['plan_interest'];
        $lead->next_follow_up_date = $data['next_follow_up_date'] ?? null;
        $lead->meeting_notes = $data['notes'];
        $lead->save();

        try {
        if ($newLeadStatus == 'Sold' || $newLeadStatus == 'Closed') {
            $lead->completed_at = now();
            $lead->save();
        }} catch (\Throwable $e) {
            Log::error('endMeeting: lead completion date update failed', ['error' => $e->getMessage()]);
        }

        // Update LeadHistory
        $leadHistory = new LeadHistory();
        $leadHistory->lead_id = $request->lead_id;
        $leadHistory->updated_by = auth()->user()->id;
        $leadHistory->timestamp = now();
        $leadHistory->status_before = $currentLeadStatus;
        $leadHistory->status_after = $newLeadStatus;
        $leadHistory->notes = $data['notes'];
        $leadHistory->action = 'Meet';
        $leadHistory->save();

        return response()->json([
            'message'    => 'Meeting ended successfully.',
            'meeting_id' => $meeting->id,
            'selfie'     => $path,
        ]);
    }
}
