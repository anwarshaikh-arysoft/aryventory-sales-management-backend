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
    public function startMeeting(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'selfie'    => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
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
            return response()->json(['message' => 'Failed to upload selfie'], 500);
        }

        // Create new meeting
        $meeting = new Meeting();
        $meeting->lead_id = $request->lead_id;
        $meeting->meeting_start_time = now();
        $meeting->meeting_start_latitude = $data['latitude'];
        $meeting->meeting_start_longitude = $data['longitude'];
        $meeting->save();

        // Save start photo as a SHOP photo (store the S3 key)
        $path = $upload['key']; // S3 object key
        $meeting->shopPhotos()->create([
            'media' => $path, // store S3 object key, not a public path
        ]);

        return response()->json([
            'message'    => 'Meeting started successfully.',
            'meeting_id' => $meeting->id,
            'shop_photo' => $path,
            'selfie_url'  => $upload['url'],
        ]);
    }

    /**
     * POST /meetings/end
     */
    public function endMeeting(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'selfie'    => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
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
            return response()->json(['message' => 'Failed to upload selfie'], 500);
        }

        // Update meeting end time
        $meeting->meeting_end_time = now();
        $meeting->meeting_end_latitude = $data['latitude'];
        $meeting->meeting_end_longitude = $data['longitude'];
        $meeting->meeting_end_notes = $data['notes'];
        $meeting->save();

        // Save end selfie as a SELFIE
        $path = $upload['key']; // S3 object key
        $meeting->selfies()->create([
            'media' => $path,
        ]);

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
        }

        try {
        // Save recording as RECORDED AUDIO
        $recPath = $recordingUpload['key']; // S3 object key
        $meeting->recordedAudios()->create([
                'media' => $recPath,
            ]);
        } catch (\Throwable $e) {
            Log::error('endMeeting: recording save failed', ['error' => $e->getMessage()]);
            // return response()->json(['message' => 'Failed to save recording'], 500);
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
