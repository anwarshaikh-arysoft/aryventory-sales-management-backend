<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\UserDailyShift;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\ObjectUploadService;

class ShiftController extends Controller
{

    // Show all shifts for the authenticated user
    // With Pagination and filtering by date
    public function showShifts(Request $request)
    {
        $user = $request->user();
        $query = UserDailyShift::where('user_id', $user->id);

        // Apply date filters
        if ($request->filter === 'today') {
            $query->whereDate('shift_date', now()->toDateString());
        } elseif ($request->filter === 'this_week') {
            $query->whereBetween('shift_date', [
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString()
            ]);
        } elseif ($request->filter === 'custom' && $request->start_date && $request->end_date) {
            $query->whereBetween('shift_date', [$request->start_date, $request->end_date]);
        }

        $shifts = $query->orderBy('shift_date', 'desc')->paginate(10);

        return response()->json([
            'message' => 'User shifts retrieved successfully.',
            'shifts' => $shifts,
        ]);
    }

    public function startShift(Request $request)
    {

        // Track time to process the request
        $startTime = Carbon::now();
        
        $user  = $request->user();
        $today = Carbon::today()->toDateString();

        $existingShift = UserDailyShift::where('user_id', $user->id)
            ->where('shift_date', $today)
            ->orderBy('shift_start', 'desc')
            ->first();

        if ($existingShift && is_null($existingShift->shift_end)) {
            return response()->json(['message' => 'Shift already started today but not ended'], 400);
        }

        $request->validate([
            'selfie'    => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        // Log::info('startShift: validation passed', ['has_selfie' => $request->hasFile('selfie')]);

        // Upload selfie to S3 using ObjectUploadService
        $uploader = new ObjectUploadService();

        // Calculate upload time
        $uploadStartTime = Carbon::now();

        try {
            $upload = $uploader->upload(
                baseDirectory: 'selfies',                 // keep consistent with shifts
                file: $request->file('selfie'),
                userId: $user->id,
                prefix: 'shift_start',
                options: [
                    'disk' => 's3',
                    'visibility' => 'private',             // signed URL returned
                    'add_date_path' => true,
                    'append_user_path' => true,
                    // 'signed_ttl' => 10,
                    // 'metadata' => ['lead_id' => (string)$data['lead_id']],
                ]
            );
        } catch (\Throwable $e) {
            Log::error('startMeeting: upload failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to upload selfie'], 500);
        }

        $uploadEndTime = Carbon::now();
        $uploadDuration = $uploadEndTime->diffInSeconds($uploadStartTime);
        Log::info('endShift: upload duration', ['upload_duration' => $uploadDuration]);

        $selfiePath = $upload['key'];  // S3 key
        $selfieUrl  = $upload['url'];   // Signed URL

        $shift = UserDailyShift::create([
            'user_id'                  => $user->id,
            'shift_date'               => $today,
            'shift_start'              => now(),
            'shift_start_selfie_image' => $selfiePath, // S3 key
            'shift_start_latitude'     => $request->latitude,
            'shift_start_longitude'    => $request->longitude,
        ]);

        Log::info('startShift: shift created', [
            'shift_id'   => $shift->id,
            'selfie_key' => $selfiePath,
            'selfie_url' => $selfieUrl,
        ]);

        $endTime = Carbon::now();
        $duration = (float) $endTime->format('U.u') - (float) $startTime->format('U.u');
        Log::info('startShift: duration', ['duration' => $duration]);

        return response()->json([
            'message'    => 'Shift started',
            'shift'      => $shift,
            'selfie_url' => $selfieUrl,
        ]);
    }

    public function startBreak(Request $request)
    {
        $startTime = Carbon::now();

        $user = $request->user();
        $shift = UserDailyShift::where('user_id', $user->id)
            ->whereDate('shift_date', Carbon::today())
            ->orderBy('shift_start', 'desc')
            ->first();

        if (!$shift) {
            return response()->json(['message' => 'No shift started'], 400);
        }

        if ($shift->break_start && !$shift->break_end) {
            return response()->json(['message' => 'Break already in progress'], 400);
        }

        $shift->update(['break_start' => Carbon::now()]);

        $endTime = Carbon::now();
        $duration = (float) $endTime->format('U.u') - (float) $startTime->format('U.u');
        Log::info('startBreak: duration', ['duration' => $duration]);

        return response()->json(['message' => 'Break started', 'shift' => $shift]);
    }

    public function endBreak(Request $request)
    {
        $startTime = Carbon::now();
        
        $user = $request->user();
        $shift = UserDailyShift::where('user_id', $user->id)
            ->whereDate('shift_date', Carbon::today())
            ->orderBy('shift_start', 'desc')
            ->first();

        if (!$shift || !$shift->break_start) {
            return response()->json(['message' => 'No break to end'], 400);
        }

        // Ensure integer minutes
        $breakMinutes = (int) Carbon::parse($shift->break_start)->diffInMinutes(Carbon::now());
        $totalBreak = (int) ($shift->total_break_mins ?? 0) + $breakMinutes;

        $shift->update([
            'break_end' => Carbon::now(),
            'total_break_mins' => $totalBreak
        ]);

        $endTime = Carbon::now();
        $duration = (float) $endTime->format('U.u') - (float) $startTime->format('U.u');
        Log::info('endBreak: duration', ['duration' => $duration]);

        return response()->json([
            'message' => 'Break ended',
            'shift' => $shift
        ]);
    }

    public function endShift(Request $request)
    {
        // Track time to process the request
        $startTime = Carbon::now();
        
        $user = $request->user();
        $shift = UserDailyShift::where('user_id', $user->id)
            ->whereDate('shift_date', Carbon::today())
            ->orderBy('shift_start', 'desc')
            ->first();

        if (!$shift) {
            return response()->json(['message' => 'No shift started'], 400);
        }

        // Validate inputs
        $validated = $request->validate([
            'selfie' => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric'
        ]);

        // Calculate upload time
        $uploadStartTime = Carbon::now();

        // Upload selfie to S3 using ObjectUploadService
        $uploader = new ObjectUploadService();

        try {
            $upload = $uploader->upload(
                baseDirectory: 'selfies',                 // keep consistent with shifts
                file: $request->file('selfie'),
                userId: $user->id,
                prefix: 'shift_end',
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

        $uploadEndTime = Carbon::now();
        $uploadDuration = $uploadEndTime->diffInSeconds($uploadStartTime);
        Log::info('endShift: upload duration', ['upload_duration' => $uploadDuration]);

        $selfiePath = $upload['key'];  // S3 key
        $selfieUrl  = $upload['url'];   // Signed URL

        $shift->shift_end = Carbon::now();
        $shift->shift_end_selfie_image = $selfiePath; // S3 key
        $shift->shift_end_latitude = $validated['latitude'] ?? $shift->shift_end_latitude;
        $shift->shift_end_longitude = $validated['longitude'] ?? $shift->shift_end_longitude;

        $shift->save();

        Log::info('endShift: shift updated', [
            'shift_id'   => $shift->id,
            'selfie_key' => $selfiePath,
            'selfie_url' => $selfieUrl,
        ]);

        $endTime = Carbon::now();
        $duration = $endTime->diffInSeconds($startTime);
        Log::info('endShift: duration', ['duration' => $duration]);

        return response()->json([
            'message' => 'Shift ended',
            'shift' => $shift,
            'selfie_url' => $selfieUrl,
        ]);
    }



    public function getShiftStatus(Request $request)
    {
        $user = $request->user();

        $shift = UserDailyShift::where('user_id', $user->id)
            ->whereDate('shift_date', Carbon::today())
            ->orderBy('shift_start', 'desc')
            ->first();

        if (!$shift) {
            return response()->json([
                'shift_started' => false,
                'shift_ended' => false,
                'break_started' => false,
                'break_ended' => false,
                'shift_timer' => 0,
                'break_timer' => 0,
            ]);
        }

        // Shift status
        $shiftStarted = (bool) $shift->shift_start;
        $shiftEnded = (bool) $shift->shift_end;

        // Break status
        $breakStarted = (bool) $shift->break_start && !$shift->break_end;
        $breakEnded = (bool) $shift->break_end;

        // Shift timer (in seconds)
        $shiftTimer = 0;
        if ($shiftStarted && !$shiftEnded) {
            $shiftTimer = Carbon::parse($shift->shift_start)->diffInSeconds(Carbon::now());
        } elseif ($shiftStarted && $shiftEnded) {
            $shiftTimer = Carbon::parse($shift->shift_start)->diffInSeconds(Carbon::parse($shift->shift_end));
        }

        // Break timer (in seconds)
        $breakTimer = 0;
        if ($breakStarted) {
            $breakTimer = Carbon::parse($shift->break_start)->diffInSeconds(Carbon::now());
        } elseif ($breakEnded) {
            $breakTimer = Carbon::parse($shift->break_start)->diffInSeconds(Carbon::parse($shift->break_end));
        }

        return response()->json([
            'shift_started' => $shiftStarted,
            'shift_ended' => $shiftEnded,
            'break_started' => $breakStarted,
            'break_ended' => $breakEnded,
            'shift_timer' => $shiftTimer,
            'break_timer' => $breakTimer,
        ]);
    }

    /**
     * Get user shifts for managers/admins to check employee shift details
     */
    public function getUserShifts(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $query = UserDailyShift::with('user:id,name,email')
            ->where('user_id', $request->user_id);

        // Apply date filters
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('shift_date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->whereDate('shift_date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->whereDate('shift_date', '<=', $request->end_date);
        }

        $perPage = $request->get('per_page', 10);
        $shifts = $query->orderBy('shift_date', 'desc')
                       ->orderBy('shift_start', 'desc')
                       ->paginate($perPage);

        // Generate signed URLs for selfies if they exist
        $shifts->getCollection()->transform(function ($shift) {
            $disk = Storage::disk('s3');
            
            if ($shift->shift_start_selfie_image) {
                try {
                    // Use call_user_func to avoid linter issues with dynamic method calls
                    if (method_exists($disk, 'temporaryUrl')) {
                        $shift->shift_start_selfie_url = call_user_func(
                            [$disk, 'temporaryUrl'], 
                            $shift->shift_start_selfie_image, 
                            now()->addMinutes(10)
                        );
                    } else {
                        $shift->shift_start_selfie_url = call_user_func(
                            [$disk, 'url'], 
                            $shift->shift_start_selfie_image
                        );
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to generate signed URL for start selfie', [
                        'shift_id' => $shift->id,
                        'error' => $e->getMessage()
                    ]);
                    $shift->shift_start_selfie_url = null;
                }
            }

            if ($shift->shift_end_selfie_image) {
                try {
                    // Use call_user_func to avoid linter issues with dynamic method calls
                    if (method_exists($disk, 'temporaryUrl')) {
                        $shift->shift_end_selfie_url = call_user_func(
                            [$disk, 'temporaryUrl'], 
                            $shift->shift_end_selfie_image, 
                            now()->addMinutes(10)
                        );
                    } else {
                        $shift->shift_end_selfie_url = call_user_func(
                            [$disk, 'url'], 
                            $shift->shift_end_selfie_image
                        );
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to generate signed URL for end selfie', [
                        'shift_id' => $shift->id,
                        'error' => $e->getMessage()
                    ]);
                    $shift->shift_end_selfie_url = null;
                }
            }

            return $shift;
        });

        return response()->json([
            'message' => 'User shifts retrieved successfully',
            'shifts' => $shifts
        ]);
    }

    /**
     * Get all users with their current shift status and recent shifts
     * For admin/manager overview
     */
    public function getAllUsersShifts(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
            'user_id' => 'nullable|exists:users,id'
        ]);

        $query = UserDailyShift::with('user:id,name,email,designation')
            ->orderBy('shift_date', 'desc')
            ->orderBy('shift_start', 'desc');

        // Filter by specific user if provided
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Apply date filters
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('shift_date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->whereDate('shift_date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->whereDate('shift_date', '<=', $request->end_date);
        }

        $perPage = $request->get('per_page', 20);
        $shifts = $query->paginate($perPage);

        // Generate signed URLs for selfies if they exist
        $shifts->getCollection()->transform(function ($shift) {
            $disk = Storage::disk('s3');
            
            if ($shift->shift_start_selfie_image) {
                try {
                    if (method_exists($disk, 'temporaryUrl')) {
                        $shift->shift_start_selfie_url = call_user_func(
                            [$disk, 'temporaryUrl'], 
                            $shift->shift_start_selfie_image, 
                            now()->addMinutes(10)
                        );
                    } else {
                        $shift->shift_start_selfie_url = call_user_func(
                            [$disk, 'url'], 
                            $shift->shift_start_selfie_image
                        );
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to generate signed URL for start selfie', [
                        'shift_id' => $shift->id,
                        'error' => $e->getMessage()
                    ]);
                    $shift->shift_start_selfie_url = null;
                }
            }

            if ($shift->shift_end_selfie_image) {
                try {
                    if (method_exists($disk, 'temporaryUrl')) {
                        $shift->shift_end_selfie_url = call_user_func(
                            [$disk, 'temporaryUrl'], 
                            $shift->shift_end_selfie_image, 
                            now()->addMinutes(10)
                        );
                    } else {
                        $shift->shift_end_selfie_url = call_user_func(
                            [$disk, 'url'], 
                            $shift->shift_end_selfie_image
                        );
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to generate signed URL for end selfie', [
                        'shift_id' => $shift->id,
                        'error' => $e->getMessage()
                    ]);
                    $shift->shift_end_selfie_url = null;
                }
            }

            return $shift;
        });

        return response()->json([
            'message' => 'All users shifts retrieved successfully',
            'shifts' => $shifts
        ]);
    }

    /**
     * Get current shift status for all users
     */
    public function getAllUsersCurrentStatus(Request $request)
    {
        $today = Carbon::today()->toDateString();
        
        $currentShifts = UserDailyShift::with('user:id,name,email,designation')
            ->whereDate('shift_date', $today)
            ->whereNotNull('shift_start')
            ->get()
            ->groupBy('user_id')
            ->map(function ($userShifts) {
                $latestShift = $userShifts->sortByDesc('shift_start')->first();
                
                $status = 'offline';
                $statusText = 'Not Started';
                $currentActivity = null;
                
                if ($latestShift->shift_start && !$latestShift->shift_end) {
                    if ($latestShift->break_start && !$latestShift->break_end) {
                        $status = 'break';
                        $statusText = 'On Break';
                        $currentActivity = 'Break started at ' . Carbon::parse($latestShift->break_start)->format('H:i');
                    } else {
                        $status = 'active';
                        $statusText = 'Active';
                        $currentActivity = 'Shift started at ' . Carbon::parse($latestShift->shift_start)->format('H:i');
                    }
                } elseif ($latestShift->shift_start && $latestShift->shift_end) {
                    $status = 'completed';
                    $statusText = 'Completed';
                    $currentActivity = 'Shift ended at ' . Carbon::parse($latestShift->shift_end)->format('H:i');
                }

                return [
                    'user' => $latestShift->user,
                    'shift' => $latestShift,
                    'status' => $status,
                    'status_text' => $statusText,
                    'current_activity' => $currentActivity,
                    'work_duration' => $latestShift->shift_start && $latestShift->shift_end 
                        ? Carbon::parse($latestShift->shift_start)->diffInMinutes(Carbon::parse($latestShift->shift_end))
                        : ($latestShift->shift_start ? Carbon::parse($latestShift->shift_start)->diffInMinutes(Carbon::now()) : 0),
                    'break_duration' => $latestShift->total_break_mins ?? 0
                ];
            });

        return response()->json([
            'message' => 'Current shift status retrieved successfully',
            'current_shifts' => $currentShifts->values()
        ]);
    }
}
