<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDailyShift;
use App\Models\Lead;
use App\Models\User;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/dashboard/overview",
     *     summary="Get dashboard overview",
     *     description="Retrieve comprehensive dashboard overview with shift counts, revenue metrics, and performance data",
     *     operationId="getDashboardOverview",
     *     tags={"Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for data filtering (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for data filtering (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="region",
     *         in="query",
     *         description="Filter by region/group ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="manager_id",
     *         in="query",
     *         description="Filter by manager ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard overview retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="shift_overview",
     *                 type="object",
     *                 @OA\Property(property="total_shifts_started", type="integer", example=25),
     *                 @OA\Property(property="active_shifts", type="integer", example=8),
     *                 @OA\Property(property="completed_shifts", type="integer", example=17)
     *             ),
     *             @OA\Property(
     *                 property="revenue_metrics",
     *                 type="object",
     *                 @OA\Property(
     *                     property="by_executive",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="user_id", type="integer", example=1),
     *                         @OA\Property(property="user_name", type="string", example="John Doe"),
     *                         @OA\Property(property="designation", type="string", example="Sales Executive"),
     *                         @OA\Property(property="group", type="string", example="North Region"),
     *                         @OA\Property(property="completed_leads", type="integer", example=5),
     *                         @OA\Property(property="estimated_revenue", type="integer", example=250000)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="by_manager",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="manager_id", type="integer", example=1),
     *                         @OA\Property(property="manager_name", type="string", example="Jane Smith"),
     *                         @OA\Property(property="completed_leads", type="integer", example=15),
     *                         @OA\Property(property="estimated_revenue", type="integer", example=750000)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="by_region",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="region_id", type="integer", example=1),
     *                         @OA\Property(property="region_name", type="string", example="North Region"),
     *                         @OA\Property(property="completed_leads", type="integer", example=20),
     *                         @OA\Property(property="estimated_revenue", type="integer", example=1000000)
     *                     )
     *                 ),
     *                 @OA\Property(property="total_completed_leads", type="integer", example=25),
     *                 @OA\Property(property="total_estimated_revenue", type="integer", example=1250000)
     *             ),
     *             @OA\Property(
     *                 property="performance_metrics",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="user_name", type="string", example="John Doe"),
     *                     @OA\Property(property="designation", type="string", example="Sales Executive"),
     *                     @OA\Property(property="role", type="string", example="Sales Executive"),
     *                     @OA\Property(property="group", type="string", example="North Region"),
     *                     @OA\Property(property="leads_created", type="integer", example=10),
     *                     @OA\Property(property="leads_completed", type="integer", example=5),
     *                     @OA\Property(property="meetings_conducted", type="integer", example=8),
     *                     @OA\Property(property="shifts_worked", type="integer", example=20),
     *                     @OA\Property(property="conversion_rate", type="number", format="float", example=50.0),
     *                     @OA\Property(property="target", type="integer", example=15),
     *                     @OA\Property(property="target_achievement", type="number", format="float", example=33.33)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="date_range",
     *                 type="object",
     *                 @OA\Property(property="start_date", type="string", example="2024-01-01"),
     *                 @OA\Property(property="end_date", type="string", example="2024-01-31")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function overview(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'region' => 'nullable|integer|exists:groups,id',
            'manager_id' => 'nullable|integer|exists:users,id',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::today();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::today();
        $regionId = $request->region;
        $managerId = $request->manager_id;

        // 1. Number of people who started their shift
        $shiftCount = $this->getShiftCount($startDate, $endDate, $regionId, $managerId);

        // 2. Revenue metrics by sales executive, manager, region
        $revenueMetrics = $this->getRevenueMetrics($startDate, $endDate, $regionId, $managerId);

        // 3. Performance metrics by user
        $performanceMetrics = $this->getPerformanceMetrics($startDate, $endDate, $regionId, $managerId);

        return response()->json([
            'shift_overview' => $shiftCount,
            'revenue_metrics' => $revenueMetrics,
            'performance_metrics' => $performanceMetrics,
            'date_range' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/dashboard/shift-locations",
     *     summary="Get shift locations",
     *     description="Retrieve shift locations for map display with GPS coordinates",
     *     operationId="getShiftLocations",
     *     tags={"Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for data filtering (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for data filtering (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="region",
     *         in="query",
     *         description="Filter by region/group ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="manager_id",
     *         in="query",
     *         description="Filter by manager ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shift locations retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="locations",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="user_name", type="string", example="John Doe"),
     *                     @OA\Property(property="designation", type="string", example="Sales Executive"),
     *                     @OA\Property(property="group", type="string", example="North Region"),
     *                     @OA\Property(property="shift_date", type="string", format="date", example="2024-01-15"),
     *                     @OA\Property(property="shift_start", type="string", format="date-time", example="2024-01-15T09:00:00Z"),
     *                     @OA\Property(property="latitude", type="number", format="float", example=28.6139),
     *                     @OA\Property(property="longitude", type="number", format="float", example=77.2090),
     *                     @OA\Property(property="is_active", type="boolean", example=true)
     *                 )
     *             ),
     *             @OA\Property(property="total_count", type="integer", example=25)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function shiftLocations(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'region' => 'nullable|integer|exists:groups,id',
            'manager_id' => 'nullable|integer|exists:users,id',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::today();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::today();
        $regionId = $request->region;
        $managerId = $request->manager_id;

        $query = UserDailyShift::with(['user:id,name,designation,group_id,manager_id', 'user.group:id,name'])
            ->whereBetween('shift_date', [$startDate, $endDate])
            ->whereNotNull('shift_start')
            ->whereNotNull('shift_start_latitude')
            ->whereNotNull('shift_start_longitude');

        // Apply filters
        if ($regionId) {
            $query->whereHas('user', function ($q) use ($regionId) {
                $q->where('group_id', $regionId);
            });
        }

        if ($managerId) {
            $query->whereHas('user', function ($q) use ($managerId) {
                $q->where('manager_id', $managerId);
            });
        }

        $shifts = $query->get()->map(function ($shift) {
            return [
                'id' => $shift->id,
                'user_name' => $shift->user->name,
                'designation' => $shift->user->designation,
                'group' => $shift->user->group->name ?? 'N/A',
                'shift_date' => $shift->shift_date,
                'shift_start' => $shift->shift_start,
                'latitude' => (float) $shift->shift_start_latitude,
                'longitude' => (float) $shift->shift_start_longitude,
                'is_active' => is_null($shift->shift_end),
            ];
        });

        return response()->json([
            'locations' => $shifts,
            'total_count' => $shifts->count(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/dashboard/revenue-breakdown",
     *     summary="Get revenue breakdown",
     *     description="Get detailed revenue breakdown by executive, manager, region, or daily",
     *     operationId="getRevenueBreakdown",
     *     tags={"Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for data filtering (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for data filtering (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="group_by",
     *         in="query",
     *         description="Group revenue by",
     *         required=false,
     *         @OA\Schema(type="string", enum={"executive", "manager", "region", "daily"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Revenue breakdown retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="revenue_breakdown",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="user_id", type="integer", example=1, nullable=true),
     *                     @OA\Property(property="user_name", type="string", example="John Doe", nullable=true),
     *                     @OA\Property(property="designation", type="string", example="Sales Executive", nullable=true),
     *                     @OA\Property(property="completed_leads", type="integer", example=5),
     *                     @OA\Property(property="estimated_revenue", type="integer", example=250000)
     *                 )
     *             ),
     *             @OA\Property(property="group_by", type="string", example="executive"),
     *             @OA\Property(
     *                 property="date_range",
     *                 type="object",
     *                 @OA\Property(property="start_date", type="string", example="2024-01-01"),
     *                 @OA\Property(property="end_date", type="string", example="2024-01-31")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function revenueBreakdown(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'group_by' => 'nullable|string|in:executive,manager,region,daily',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();
        $groupBy = $request->group_by ?? 'executive';

        $revenue = $this->getDetailedRevenue($startDate, $endDate, $groupBy);

        return response()->json([
            'revenue_breakdown' => $revenue,
            'group_by' => $groupBy,
            'date_range' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/dashboard/user-performance",
     *     summary="Get user performance",
     *     description="Get detailed user performance metrics with daily breakdown",
     *     operationId="getUserPerformance",
     *     tags={"Dashboard"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for data filtering (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for data filtering (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Filter by specific user ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User performance retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="performance_data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="user_name", type="string", example="John Doe"),
     *                     @OA\Property(property="designation", type="string", example="Sales Executive"),
     *                     @OA\Property(property="role", type="string", example="Sales Executive"),
     *                     @OA\Property(property="group", type="string", example="North Region"),
     *                     @OA\Property(
     *                         property="daily_performance",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="date", type="string", example="2024-01-15"),
     *                             @OA\Property(property="leads_created", type="integer", example=2),
     *                             @OA\Property(property="leads_completed", type="integer", example=1),
     *                             @OA\Property(property="meetings_conducted", type="integer", example=3),
     *                             @OA\Property(property="shift_worked", type="boolean", example=true)
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="totals",
     *                         type="object",
     *                         @OA\Property(property="leads_created", type="integer", example=20),
     *                         @OA\Property(property="leads_completed", type="integer", example=10),
     *                         @OA\Property(property="conversion_rate", type="number", format="float", example=50.0)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="date_range",
     *                 type="object",
     *                 @OA\Property(property="start_date", type="string", example="2024-01-01"),
     *                 @OA\Property(property="end_date", type="string", example="2024-01-31")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function userPerformance(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();
        $userId = $request->user_id;

        $performance = $this->getDetailedPerformance($startDate, $endDate, $userId);

        return response()->json([
            'performance_data' => $performance,
            'date_range' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Helper method to get shift count
     */
    private function getShiftCount($startDate, $endDate, $regionId = null, $managerId = null)
    {
        $query = UserDailyShift::with(['user:id,name,group_id,manager_id'])
            ->whereBetween('shift_date', [$startDate, $endDate])
            ->whereNotNull('shift_start');

        // Apply filters
        if ($regionId) {
            $query->whereHas('user', function ($q) use ($regionId) {
                $q->where('group_id', $regionId);
            });
        }

        if ($managerId) {
            $query->whereHas('user', function ($q) use ($managerId) {
                $q->where('manager_id', $managerId);
            });
        }

        $totalShifts = $query->count();
        $activeShifts = $query->whereNull('shift_end')->count();
        $completedShifts = $query->whereNotNull('shift_end')->count();

        return [
            'total_shifts_started' => $totalShifts,
            'active_shifts' => $activeShifts,
            'completed_shifts' => $completedShifts,
        ];
    }

    /**
     * Helper method to get revenue metrics
     */
    private function getRevenueMetrics($startDate, $endDate, $regionId = null, $managerId = null)
    {
        $query = Lead::with(['assignedToUser:id,name,designation,group_id,manager_id', 'assignedToUser.group:id,name'])
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$startDate, $endDate]);

        // Apply filters
        if ($regionId) {
            $query->whereHas('assignedToUser', function ($q) use ($regionId) {
                $q->where('group_id', $regionId);
            });
        }

        if ($managerId) {
            $query->whereHas('assignedToUser', function ($q) use ($managerId) {
                $q->where('manager_id', $managerId);
            });
        }

        $completedLeads = $query->get();

        // Group by sales executive
        $revenueByExecutive = $completedLeads->groupBy('assigned_to')->map(function ($leads, $userId) {
            $user = $leads->first()->assignedToUser;
            return [
                'user_id' => $userId,
                'user_name' => $user->name,
                'designation' => $user->designation,
                'group' => $user->group->name ?? 'N/A',
                'completed_leads' => $leads->count(),
                // Note: Actual revenue calculation would depend on lead value/plan pricing
                'estimated_revenue' => $leads->count() * 50000, // Placeholder calculation
            ];
        })->values();

        // Group by manager
        $revenueByManager = $completedLeads->groupBy('assignedToUser.manager_id')->map(function ($leads, $managerId) {
            if (!$managerId) return null;
            
            $manager = User::find($managerId);
            return [
                'manager_id' => $managerId,
                'manager_name' => $manager->name ?? 'N/A',
                'completed_leads' => $leads->count(),
                'estimated_revenue' => $leads->count() * 50000, // Placeholder calculation
            ];
        })->filter()->values();

        // Group by region
        $revenueByRegion = $completedLeads->groupBy('assignedToUser.group_id')->map(function ($leads, $groupId) {
            if (!$groupId) return null;
            
            $group = $leads->first()->assignedToUser->group;
            return [
                'region_id' => $groupId,
                'region_name' => $group->name ?? 'N/A',
                'completed_leads' => $leads->count(),
                'estimated_revenue' => $leads->count() * 50000, // Placeholder calculation
            ];
        })->filter()->values();

        return [
            'by_executive' => $revenueByExecutive,
            'by_manager' => $revenueByManager,
            'by_region' => $revenueByRegion,
            'total_completed_leads' => $completedLeads->count(),
            'total_estimated_revenue' => $completedLeads->count() * 50000, // Placeholder calculation
        ];
    }

    /**
     * Helper method to get performance metrics
     */
    private function getPerformanceMetrics($startDate, $endDate, $regionId = null, $managerId = null)
    {
        $userQuery = User::with(['group:id,name', 'targets', 'role:id,name']);

        // Apply filters
        if ($regionId) {
            $userQuery->where('group_id', $regionId);
        }

        if ($managerId) {
            $userQuery->where('manager_id', $managerId);
        }

        $users = $userQuery->get();

        $performance = $users->map(function ($user) use ($startDate, $endDate) {
            // Get user's leads in the date range
            $leadsCreated = Lead::where('assigned_to', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $leadsCompleted = Lead::where('assigned_to', $user->id)
                ->whereNotNull('completed_at')
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->count();

            // Get meetings conducted
            $meetingsConducted = Meeting::whereHas('lead', function ($q) use ($user) {
                $q->where('assigned_to', $user->id);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

            // Get shift attendance
            $shiftsWorked = UserDailyShift::where('user_id', $user->id)
                ->whereBetween('shift_date', [$startDate, $endDate])
                ->whereNotNull('shift_start')
                ->count();

            // Calculate conversion rate
            $conversionRate = $leadsCreated > 0 ? round(($leadsCompleted / $leadsCreated) * 100, 2) : 0;

            // Get target (assuming monthly targets)
            $target = $user->targets->first();
            $targetAchievement = $target && $target->closure_target > 0 
                ? round(($leadsCompleted / $target->closure_target) * 100, 2) 
                : 0;

            return [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'designation' => $user->designation,
                'role' => $user->role->name ?? 'N/A',
                'group' => $user->group->name ?? 'N/A',
                'leads_created' => $leadsCreated,
                'leads_completed' => $leadsCompleted,
                'meetings_conducted' => $meetingsConducted,
                'shifts_worked' => $shiftsWorked,
                'conversion_rate' => $conversionRate,
                'target' => $target ? $target->closure_target : 0,
                'target_achievement' => $targetAchievement,
            ];
        });

        return $performance;
    }

    /**
     * Helper method to get detailed revenue breakdown
     */
    private function getDetailedRevenue($startDate, $endDate, $groupBy)
    {
        $query = Lead::with(['assignedToUser:id,name,designation,group_id,manager_id', 'assignedToUser.group:id,name'])
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$startDate, $endDate]);

        switch ($groupBy) {
            case 'daily':
                return $query->get()
                    ->groupBy(function ($lead) {
                        return Carbon::parse($lead->completed_at)->format('Y-m-d');
                    })
                    ->map(function ($leads, $date) {
                        return [
                            'date' => $date,
                            'completed_leads' => $leads->count(),
                            'estimated_revenue' => $leads->count() * 50000,
                        ];
                    })
                    ->values();

            case 'manager':
                return $query->get()
                    ->groupBy('assignedToUser.manager_id')
                    ->map(function ($leads, $managerId) {
                        if (!$managerId) return null;
                        
                        $manager = User::find($managerId);
                        return [
                            'manager_id' => $managerId,
                            'manager_name' => $manager->name ?? 'N/A',
                            'completed_leads' => $leads->count(),
                            'estimated_revenue' => $leads->count() * 50000,
                        ];
                    })
                    ->filter()
                    ->values();

            case 'region':
                return $query->get()
                    ->groupBy('assignedToUser.group_id')
                    ->map(function ($leads, $groupId) {
                        if (!$groupId) return null;
                        
                        $group = $leads->first()->assignedToUser->group;
                        return [
                            'region_id' => $groupId,
                            'region_name' => $group->name ?? 'N/A',
                            'completed_leads' => $leads->count(),
                            'estimated_revenue' => $leads->count() * 50000,
                        ];
                    })
                    ->filter()
                    ->values();

            default: // executive
                return $query->get()
                    ->groupBy('assigned_to')
                    ->map(function ($leads, $userId) {
                        $user = $leads->first()->assignedToUser;
                        return [
                            'user_id' => $userId,
                            'user_name' => $user->name,
                            'designation' => $user->designation,
                            'completed_leads' => $leads->count(),
                            'estimated_revenue' => $leads->count() * 50000,
                        ];
                    })
                    ->values();
        }
    }

    /**
     * Helper method to get detailed user performance
     */
    private function getDetailedPerformance($startDate, $endDate, $userId = null)
    {
        $userQuery = User::with(['group:id,name', 'targets', 'role:id,name']);

        if ($userId) {
            $userQuery->where('id', $userId);
        }

        $users = $userQuery->get();

        return $users->map(function ($user) use ($startDate, $endDate) {
            // Daily performance breakdown
            $dailyPerformance = [];
            $currentDate = $startDate->copy();
            
            while ($currentDate <= $endDate) {
                $dayStart = $currentDate->copy()->startOfDay();
                $dayEnd = $currentDate->copy()->endOfDay();

                $leadsCreated = Lead::where('assigned_to', $user->id)
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->count();

                $leadsCompleted = Lead::where('assigned_to', $user->id)
                    ->whereNotNull('completed_at')
                    ->whereBetween('completed_at', [$dayStart, $dayEnd])
                    ->count();

                $meetingsConducted = Meeting::whereHas('lead', function ($q) use ($user) {
                    $q->where('assigned_to', $user->id);
                })
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->count();

                $shiftWorked = UserDailyShift::where('user_id', $user->id)
                    ->where('shift_date', $currentDate->format('Y-m-d'))
                    ->whereNotNull('shift_start')
                    ->exists();

                $dailyPerformance[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'leads_created' => $leadsCreated,
                    'leads_completed' => $leadsCompleted,
                    'meetings_conducted' => $meetingsConducted,
                    'shift_worked' => $shiftWorked,
                ];

                $currentDate->addDay();
            }

            // Overall totals
            $totalLeadsCreated = Lead::where('assigned_to', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $totalLeadsCompleted = Lead::where('assigned_to', $user->id)
                ->whereNotNull('completed_at')
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->count();

            return [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'designation' => $user->designation,
                'role' => $user->role->name ?? 'N/A',
                'group' => $user->group->name ?? 'N/A',
                'daily_performance' => $dailyPerformance,
                'totals' => [
                    'leads_created' => $totalLeadsCreated,
                    'leads_completed' => $totalLeadsCompleted,
                    'conversion_rate' => $totalLeadsCreated > 0 ? round(($totalLeadsCompleted / $totalLeadsCreated) * 100, 2) : 0,
                ],
            ];
        });
    }
}

