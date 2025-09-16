<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\UserDailyShift;
use App\Models\UserBreak;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\ObjectUploadService;

class ShiftController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/shifts",
     *     summary="Get user shifts",
     *     description="Retrieve all shifts for the authenticated user with pagination and filtering",
     *     operationId="showShifts",
     *     tags={"Shifts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="filter",
     *         in="query",
     *         description="Filter shifts by date",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"today", "this_week", "custom"}
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for custom filter (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for custom filter (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User shifts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User shifts retrieved successfully."),
     *             @OA\Property(
     *                 property="shifts",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/UserDailyShift")),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/shifts/start",
     *     summary="Start a shift",
     *     description="Start a new shift for the authenticated user",
     *     operationId="startShift",
     *     tags={"Shifts"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="selfie", type="string", format="binary", description="Selfie image"),
     *                 @OA\Property(property="latitude", type="number", format="float", description="Location latitude"),
     *                 @OA\Property(property="longitude", type="number", format="float", description="Location longitude"),
     *                 @OA\Property(property="address", type="string", description="Location address")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Shift started successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Shift started successfully."),
     *             @OA\Property(property="shift", ref="#/components/schemas/UserDailyShift")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Shift already started or validation failed"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
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
            // return response()->json(['message' => 'Failed to upload selfie'], 500);
            $selfiePath = null;
            $selfieUrl = null;
        }

        $uploadEndTime = Carbon::now();
        $uploadDuration = $uploadEndTime->diffInSeconds($uploadStartTime);
        Log::info('endShift: upload duration', ['upload_duration' => $uploadDuration]);

        try {
            $selfiePath = $upload['key'];  // S3 key
            $selfieUrl  = $upload['url'];   // Signed URL
        } catch (\Throwable $e) {
            Log::error('startShift: upload failed', ['error' => $e->getMessage()]);
            $selfiePath = null;
            $selfieUrl = null;
        }

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
            // return response()->json(['message' => 'Failed to upload selfie'], 500);
            $selfiePath = null;
            $selfieUrl = null;
        }

        $uploadEndTime = Carbon::now();
        $uploadDuration = $uploadEndTime->diffInSeconds($uploadStartTime);
        Log::info('endShift: upload duration', ['upload_duration' => $uploadDuration]);

        try {
            $selfiePath = $upload['key'];  // S3 key
            $selfieUrl  = $upload['url'];   // Signed URL
        } catch (\Throwable $e) {
            Log::error('endShift: upload failed', ['error' => $e->getMessage()]);
            $selfiePath = null;
            $selfieUrl = null;
        }

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

        // Check if there's already an active break
        if ($shift->isOnBreak()) {
            return response()->json(['message' => 'Break already in progress'], 400);
        }

        // Validate break type if provided
        $request->validate([
            'break_type' => 'nullable|in:lunch,coffee,personal,other',
            'notes' => 'nullable|string|max:500'
        ]);

        // Create new break record
        $break = UserBreak::create([
            'user_daily_shift_id' => $shift->id,
            'break_start' => Carbon::now(),
            'break_type' => $request->break_type ?? 'other',
            'notes' => $request->notes
        ]);

        $endTime = Carbon::now();
        $duration = (float) $endTime->format('U.u') - (float) $startTime->format('U.u');
        Log::info('startBreak: duration', ['duration' => $duration, 'break_id' => $break->id]);

        return response()->json([
            'message' => 'Break started',
            'break' => $break,
            'shift' => $shift->fresh()
        ]);
    }

    public function endBreak(Request $request)
    {
        $startTime = Carbon::now();

        $user = $request->user();
        $shift = UserDailyShift::where('user_id', $user->id)
            ->whereDate('shift_date', Carbon::today())
            ->orderBy('shift_start', 'desc')
            ->first();

        if (!$shift) {
            return response()->json(['message' => 'No shift found'], 400);
        }

        // Get the current active break
        $activeBreak = $shift->getCurrentBreak();

        if (!$activeBreak) {
            return response()->json(['message' => 'No active break to end'], 400);
        }

        // End the break and calculate duration
        $breakEnd = Carbon::now();
        $breakDuration = $activeBreak->break_start->diffInMinutes($breakEnd);

        $activeBreak->update([
            'break_end' => $breakEnd,
            'break_duration_mins' => $breakDuration
        ]);

        // Update the shift's total break time (for backward compatibility)
        $totalBreakTime = (int) $shift->calculateTotalBreakTime();
        $shift->update(['total_break_mins' => $totalBreakTime]);

        $endTime = Carbon::now();
        $duration = (float) $endTime->format('U.u') - (float) $startTime->format('U.u');
        Log::info('endBreak: duration', ['duration' => $duration, 'break_id' => $activeBreak->id, 'break_duration' => $breakDuration]);

        return response()->json([
            'message' => 'Break ended',
            'break' => $activeBreak->fresh(),
            'shift' => $shift->fresh()
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
                'total_break_time' => 0,
            ]);
        }

        // Shift status
        $shiftStarted = (bool) $shift->shift_start;
        $shiftEnded = (bool) $shift->shift_end;

        // Break status - check for active break
        $activeBreak = $shift->getCurrentBreak();
        $breakStarted = (bool) $activeBreak;
        $breakEnded = !$breakStarted && $shift->breaks()->completed()->exists();

        // Shift timer (in seconds)
        $shiftTimer = 0;
        if ($shiftStarted && !$shiftEnded) {
            $shiftTimer = Carbon::parse($shift->shift_start)->diffInSeconds(Carbon::now());
        } elseif ($shiftStarted && $shiftEnded) {
            $shiftTimer = Carbon::parse($shift->shift_start)->diffInSeconds(Carbon::parse($shift->shift_end));
        }

        // Break timer (in seconds) - current active break
        $breakTimer = 0;
        if ($activeBreak) {
            $breakTimer = $activeBreak->break_start->diffInSeconds(Carbon::now());
        }

        // Total break time (in minutes)
        $totalBreakTime = $shift->calculateTotalBreakTime() * 60 ?? 0;

        return response()->json([
            'shift_started' => $shiftStarted,
            'shift_ended' => $shiftEnded,
            'break_started' => $breakStarted,
            'break_ended' => $breakEnded,
            'shift_timer' => $shiftTimer,
            'break_timer' => $breakTimer,
            'total_break_time' => $totalBreakTime,
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

    /**
     * Get all breaks for the current shift
     */
    public function getBreaks(Request $request)
    {
        $user = $request->user();
        $shift = UserDailyShift::where('user_id', $user->id)
            ->whereDate('shift_date', Carbon::today())
            ->orderBy('shift_start', 'desc')
            ->first();

        if (!$shift) {
            return response()->json(['message' => 'No shift found'], 400);
        }

        $breaks = $shift->breaks()->orderBy('break_start', 'desc')->get();

        return response()->json([
            'message' => 'Breaks retrieved successfully',
            'breaks' => $breaks,
            'total_break_time' => $shift->total_break_time
        ]);
    }

    /**
     * Get break history for a specific date range
     */
    public function getBreakHistory(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'break_type' => 'nullable|in:lunch,coffee,personal,other'
        ]);

        $user = $request->user();
        $query = UserBreak::whereHas('userDailyShift', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        // Apply date filters
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereHas('userDailyShift', function ($q) use ($request) {
                $q->whereBetween('shift_date', [$request->start_date, $request->end_date]);
            });
        }

        // Apply break type filter
        if ($request->filled('break_type')) {
            $query->where('break_type', $request->break_type);
        }

        $breaks = $query->with('userDailyShift')
            ->orderBy('break_start', 'desc')
            ->paginate(20);

        return response()->json([
            'message' => 'Break history retrieved successfully',
            'breaks' => $breaks
        ]);
    }

    /**
     * Update break details (notes, type)
     */
    public function updateBreak(Request $request, $breakId)
    {
        $request->validate([
            'break_type' => 'nullable|in:lunch,coffee,personal,other',
            'notes' => 'nullable|string|max:500'
        ]);

        $user = $request->user();
        $break = UserBreak::whereHas('userDailyShift', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->findOrFail($breakId);

        $break->update($request->only(['break_type', 'notes']));

        return response()->json([
            'message' => 'Break updated successfully',
            'break' => $break->fresh()
        ]);
    }

    /**
     * Delete a break (only if it's not the current active break)
     */
    public function deleteBreak(Request $request, $breakId)
    {
        $user = $request->user();
        $break = UserBreak::whereHas('userDailyShift', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->findOrFail($breakId);

        // Don't allow deleting active breaks
        if ($break->isActive()) {
            return response()->json(['message' => 'Cannot delete active break'], 400);
        }

        $break->delete();

        // Update total break time for the shift
        $shift = $break->userDailyShift;
        $totalBreakTime = $shift->calculateTotalBreakTime();
        $shift->update(['total_break_mins' => $totalBreakTime]);

        return response()->json([
            'message' => 'Break deleted successfully'
        ]);
    }
}
