<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\UserDailyShift;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $user = $request->user();
        $today = Carbon::today()->toDateString();

        // Prevent shift start before previous shift end in a day
        // Find the most recent shift today
        $existingShift = UserDailyShift::where('user_id', $user->id)
            ->where('shift_date', $today)
            ->orderBy('shift_start', 'desc')
            ->first();

        // Show error if existing shift for the day is not ended 
        if ($existingShift && is_null($existingShift->shift_end)) {
            return response()->json([
                'message' => 'Shift already started today but not ended'
            ], 400);
        }

        // Validate inputs
        $request->validate([
            'selfie' => 'nullable|image|mimes:jpeg,jpg,png|max:5120', // 5MB
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric'
        ]);

        // Handle selfie upload locally
        $selfiePath = null;
        if ($request->hasFile('selfie')) {
            $selfiePath = $request->file('selfie')->store('selfies', 'public');
        }        

        // Create shift record
        $shift = UserDailyShift::create([
            'user_id' => $user->id,
            'shift_date' => $today,
            'shift_start' => Carbon::now(),
            'shift_start_selfie_image' => $selfiePath,
            'shift_start_latitude' => $request->latitude,
            'shift_start_longitude' => $request->longitude,
        ]);

        return response()->json([
            'message' => 'Shift started',
            'shift' => $shift
        ]);
    }

    // public function startShift(Request $request)
    // {
    //     // Add useful context to every log line in this request
    //     Log::withContext([
    //         'route'   => 'startShift',
    //         'user_id' => optional($request->user())->id,
    //         'ip'      => $request->ip(),
    //         'time'    => now()->toIso8601String(),
    //     ]);

    //     Log::info('startShift: incoming request', [
    //         'has_selfie' => $request->hasFile('selfie'),
    //         'latitude'   => $request->input('latitude'),
    //         'longitude'  => $request->input('longitude'),
    //         'disk'       => config('filesystems.default'),
    //         's3_bucket'  => config('filesystems.disks.s3.bucket'),
    //         's3_region'  => config('filesystems.disks.s3.region'),
    //     ]);

    //     try {
    //         $user  = $request->user();
    //         $today = Carbon::today()->toDateString();

    //         $existingShift = UserDailyShift::where('user_id', $user->id)
    //             ->where('shift_date', $today)
    //             ->orderBy('shift_start', 'desc')
    //             ->first();

    //         if ($existingShift && is_null($existingShift->shift_end)) {
    //             Log::warning('startShift: shift already started but not ended', ['existing_shift_id' => $existingShift->id]);
    //             return response()->json([
    //                 'message' => 'Shift already started today but not ended'
    //             ], 400);
    //         }

    //         // Validate
    //         $request->validate([
    //             'selfie'    => 'nullable|image|mimes:jpeg,jpg,png|max:5120',
    //             'latitude'  => 'nullable|numeric',
    //             'longitude' => 'nullable|numeric',
    //         ]);
    //         Log::info('startShift: validation passed');

    //         $selfiePath = null;
    //         $selfieUrl  = null;

    //         if ($request->hasFile('selfie')) {
    //             $file = $request->file('selfie');

    //             Log::info('startShift: selfie file metadata', [
    //                 'original_name' => $file->getClientOriginalName(),
    //                 'ext'           => $file->getClientOriginalExtension(),
    //                 'mime'          => $file->getMimeType(),
    //                 'size_bytes'    => $file->getSize(),
    //                 'is_valid'      => $file->isValid(),
    //             ]);

    //             if (!$file->isValid()) {
    //                 Log::error('startShift: uploaded file is not valid');
    //                 return response()->json(['message' => 'Invalid selfie upload'], 422);
    //             }

    //             $directory = 'selfies/' . $user->id . '/' . now()->format('Y/m/d');
    //             $filename  = 'start_' . Str::uuid() . '.' . $file->getClientOriginalExtension();

    //             Log::info('startShift: attempting S3 upload', [
    //                 'disk'      => 's3',
    //                 'directory' => $directory,
    //                 'filename'  => $filename,
    //             ]);

    //             // Use Storage API directly — clearer failures and easier to mock/test
    //             $selfiePath = Storage::disk('s3')->putFileAs($directory, $file, $filename, [
    //                 'visibility' => 'public', // change to 'private' if you prefer signed URLs only
    //             ]) ? ($directory . '/' . $filename) : null;

    //             Log::info('startShift: upload result', ['selfie_path' => $selfiePath]);

    //             if (!$selfiePath) {
    //                 Log::error('startShift: S3 upload reported failure');
    //                 return response()->json(['message' => 'Failed to upload selfie'], 500);
    //             }

    //             // Double-check the object exists
    //             $exists = Storage::disk('s3')->exists($selfiePath);
    //             Log::info('startShift: existence check', ['exists' => $exists]);
    //             if (!$exists) {
    //                 Log::error('startShift: uploaded key not found on S3', ['key' => $selfiePath]);
    //                 return response()->json(['message' => 'Upload verification failed'], 500);
    //             }

    //             // Build a URL the right way:
    //             // - If object is public or AWS_URL is set properly, Storage::url works.
    //             // - For private objects, use a temporary signed URL.
    //             try {
    //                 // Try public URL first
    //                 $selfieUrl = Storage::disk('s3')->url($selfiePath);
    //                 Log::info('startShift: Storage::url generated', ['url' => $selfieUrl]);
    //             } catch (\Throwable $e) {
    //                 Log::warning('startShift: Storage::url failed, will try temporaryUrl', [
    //                     'error' => $e->getMessage(),
    //                 ]);
    //             }

    //             if (!$selfieUrl) {
    //                 // Fallback to signed URL (works for private buckets)
    //                 $selfieUrl = Storage::disk('s3')->temporaryUrl($selfiePath, now()->addMinutes(10));
    //                 Log::info('startShift: temporaryUrl generated', ['url' => $selfieUrl]);
    //             }
    //         } else {
    //             Log::info('startShift: no selfie uploaded');
    //         }

    //         // NOTE: You are currently storing the S3 *key* (not the full URL) in DB. That’s fine.
    //         // If you want to store the URL as well, add a column and save it too.
    //         $shift = UserDailyShift::create([
    //             'user_id'                  => $user->id,
    //             'shift_date'               => $today,
    //             'shift_start'              => now(),
    //             'shift_start_selfie_image' => $selfiePath, // key in S3
    //             'shift_start_latitude'     => $request->latitude,
    //             'shift_start_longitude'    => $request->longitude,
    //             // 'shift_start_selfie_url' => $selfieUrl, // uncomment if you add this column
    //         ]);

    //         Log::info('startShift: shift created', [
    //             'shift_id'   => $shift->id,
    //             'selfie_key' => $selfiePath,
    //             'selfie_url' => $selfieUrl,
    //         ]);

    //         return response()->json([
    //             'message'    => 'Shift started',
    //             'shift'      => $shift,
    //             'selfie_url' => $selfieUrl, // null if no selfie
    //         ]);
    //     } catch (\Throwable $e) {
    //         Log::error('startShift: unhandled exception', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);
    //         return response()->json(['message' => 'Something went wrong'], 500);
    //     }
    // }

    public function startBreak(Request $request)
    {
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

        return response()->json(['message' => 'Break started', 'shift' => $shift]);
    }

    public function endBreak(Request $request)
    {
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

        return response()->json([
            'message' => 'Break ended',
            'shift' => $shift
        ]);
    }


    public function endShift(Request $request)
    {
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

        // Handle selfie at shift end (optional)
        if ($request->hasFile('selfie')) {
            $selfiePath = $request->file('selfie')->store('selfies', 'public');
            $shift->shift_end_selfie_image = $selfiePath;
        }

        $shift->shift_end = Carbon::now();
        $shift->shift_end_latitude = $validated['latitude'] ?? $shift->shift_end_latitude;
        $shift->shift_end_longitude = $validated['longitude'] ?? $shift->shift_end_longitude;

        $shift->save();

        return response()->json([
            'message' => 'Shift ended',
            'shift' => $shift
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
}
