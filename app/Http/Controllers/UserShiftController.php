<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserDailyShift;
use App\Models\Meeting;
use App\Services\DistanceCalculationService;
use Inertia\Inertia;

class UserShiftController extends Controller
{
    /**
     * Display detailed shift information for a specific user
     */
    public function show(Request $request, User $user)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $query = UserDailyShift::where('user_id', $user->id);

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

        // Enhance each shift with meeting data
        $shifts->getCollection()->transform(function ($shift) {
            // Get meetings for this shift date
            $meetings = Meeting::with([
                'lead:id,shop_name,contact_person,mobile_number,address,area_locality,pincode,gps_location,business_type,current_system,lead_status,plan_interest',
                'lead.businessTypeData:id,name',
                'lead.currentSystemData:id,name',
                'lead.leadStatusData:id,name'
            ])
            ->whereDate('meeting_start_time', $shift->shift_date)
            ->orderBy('meeting_start_time', 'asc')
            ->get();

            // Process meetings to add time and distance calculations
            $processedMeetings = $meetings->map(function ($meeting, $index) use ($meetings) {
                $meetingData = $meeting->toArray();
                
                // Add lead details
                $meetingData['lead_details'] = $meeting->lead;
                
                // Calculate time from previous meeting
                if ($index > 0) {
                    $previousMeeting = $meetings[$index - 1];
                    $timeDiff = DistanceCalculationService::calculateTimeDifference(
                        $previousMeeting->meeting_end_time ?? $previousMeeting->meeting_start_time,
                        $meeting->meeting_start_time
                    );
                    $meetingData['time_from_previous_meeting'] = $timeDiff;
                } else {
                    $meetingData['time_from_previous_meeting'] = null;
                }

                // Calculate distance from previous meeting
                if ($index > 0 && 
                    $meeting->meeting_start_latitude && 
                    $meeting->meeting_start_longitude &&
                    $meetings[$index - 1]->meeting_end_latitude && 
                    $meetings[$index - 1]->meeting_end_longitude) {
                    
                    $distance = DistanceCalculationService::calculateDistanceInKm(
                        $meetings[$index - 1]->meeting_end_latitude,
                        $meetings[$index - 1]->meeting_end_longitude,
                        $meeting->meeting_start_latitude,
                        $meeting->meeting_start_longitude
                    );
                    
                    $meetingData['distance_from_previous_meeting'] = [
                        'kilometers' => round($distance, 2),
                        'meters' => round($distance * 1000, 0)
                    ];
                } else {
                    $meetingData['distance_from_previous_meeting'] = null;
                }

                return $meetingData;
            });

            // Add meetings to shift data
            $shiftData = $shift->toArray();
            $shiftData['meetings'] = $processedMeetings;
            $shiftData['meetings_count'] = $processedMeetings->count();
            
            // Calculate total meeting time for the day
            $totalMeetingTime = 0;
            foreach ($processedMeetings as $meeting) {
                if ($meeting['meeting_start_time'] && $meeting['meeting_end_time']) {
                    $timeDiff = DistanceCalculationService::calculateTimeDifference(
                        $meeting['meeting_start_time'],
                        $meeting['meeting_end_time']
                    );
                    $totalMeetingTime += $timeDiff['total_minutes'];
                }
            }
            $shiftData['total_meeting_time_minutes'] = $totalMeetingTime;

            return $shiftData;
        });

        return Inertia::render('user-shifts', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'designation' => $user->designation
            ],
            'shifts' => $shifts,
            'filters' => $request->only(['start_date', 'end_date', 'per_page'])
        ]);
    }
}
