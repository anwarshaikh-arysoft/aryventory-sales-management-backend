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
        $data = $request->validate([
            'selfie'    => ['required','image','mimes:jpeg,jpg,png','max:5120'],
            'latitude'  => ['required','numeric','between:-90,90'],
            'longitude' => ['required','numeric','between:-180,180'],
            'lead_id'   => ['required','integer'],
        ]);

        // check if meeting already exists
        $meeting = Meeting::where('lead_id', $request->lead_id)->where('meeting_end_time', null)->first();
        if ($meeting) {
            return response()->json(['message' => 'Meeting already exists'], 422);
        }

        // Create new meeting
        $meeting = new Meeting();
        $meeting->lead_id = $request->lead_id;
        $meeting->meeting_start_time = now();
        $meeting->meeting_start_latitude = $data['latitude'];
        $meeting->meeting_start_longitude = $data['longitude'];
        $meeting->save();

        // Save start photo as a SHOP photo
        $path = $request->file('selfie')->store('meetings/shop_photos', 'public');
        $meeting->shopPhotos()->create([
            'media' => $path,
        ]);

        return response()->json([
            'message'    => 'Meeting started successfully.',
            'meeting_id' => $meeting->id,
            'shop_photo' => $path,
        ]);
    }

    /**
     * POST /meetings/end
     */
    public function endMeeting(Request $request)
    {
        $data = $request->validate([
            'selfie'    => ['required','image','mimes:jpeg,jpg,png','max:5120'],
            'latitude'  => ['required','numeric','between:-90,90'],
            'longitude' => ['required','numeric','between:-180,180'],
            'lead_id'   => ['required','integer'],
            'lead_status_id' => ['required','integer'],
            // Add 3pg support        
            'recording' => ['required','file','mimes:mp3,3gp,mp4,aac,m4a,wav,ogg','max:30720'],
            // 'recording' => ['required','file'],
            'notes' => ['nullable','string','max:1000'],
            'next_follow_up_date' => ['required', 'string'],
        ]);

        Log::info($data);

        // check if meeting already exists
        $meeting = Meeting::where('lead_id', $request->lead_id)->where('meeting_end_time', null)->first();
        if (!$meeting) {
            return response()->json(['message' => 'Meeting not found'], 422);
        }

        // Update meeting end time
        $meeting->meeting_end_time = now();
        $meeting->meeting_end_latitude = $data['latitude'];
        $meeting->meeting_end_longitude = $data['longitude'];
        $meeting->meeting_end_notes = $data['notes'];
        $meeting->save();

        // Save end selfie as a SELFIE
        $path = $request->file('selfie')->store('meetings/selfies', 'public');
        $meeting->selfies()->create([
            'media' => $path,
        ]);

        // Save recording if attached
        if ($request->hasFile('recording')) {
            $recPath = $request->file('recording')->store('meetings/recordings', 'public');
            $meeting->recordedAudios()->create([
                'media' => $recPath,
            ]);
        }

        // Current Lead Status
        $lead = Lead::find($request->lead_id);
        $leadStatusId = $lead->lead_status;
        $currentLeadStatus = LeadStatus::find($leadStatusId)->name;
        $newLeadStatus = LeadStatus::find($data['lead_status_id'])->name;

        // update lead status as per the lead_status_id
        $lead = Lead::find($request->lead_id);
        $lead->lead_status = $data['lead_status_id'];
        $lead->next_follow_up_date = $data['next_follow_up_date'];
        $lead->meeting_notes = $data['notes'];
        $lead->save();

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
