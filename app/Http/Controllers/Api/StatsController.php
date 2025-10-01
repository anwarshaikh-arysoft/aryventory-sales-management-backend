<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\Meeting;
use App\Models\Target;
use Carbon\Carbon;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\LeadStatus;

class StatsController extends Controller
{
    //
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            $totalLeads = Lead::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->where('assigned_to', $user->id)
                ->count();

            $sold = Lead::whereBetween('completed_at', [$startOfMonth, $endOfMonth])
                ->where('assigned_to', $user->id)
                ->count();

            // Get No follow up statuses
            $noFollowUpStatuses = LeadStatus::whereIn('name', ['Sold', 'Already Using CRM', 'Not Interested', 'Using Different App'])->pluck('id');
        
            $followUps = Lead::whereDate('next_follow_up_date', '=', Carbon::today())                
                ->where('assigned_to', $user->id)
                ->whereNotIn('lead_status', $noFollowUpStatuses)
                ->count();

            $revenueByPlan = Lead::select(
                'leads.plan_interest',
                DB::raw('COUNT(leads.id) as leads_count'),
                DB::raw('COALESCE(SUM(plans.amount), 0) as revenue')
            )
                ->leftJoin('plans', 'plans.name', '=', 'leads.plan_interest')
                ->whereBetween('leads.completed_at', [$startOfMonth, $endOfMonth])
                ->where('leads.lead_status', '5')
                ->where('leads.assigned_to', $user->id)
                ->groupBy('leads.plan_interest', 'plans.amount') // include plans.amount for strict SQL modes
                ->get();

            $revenue = $revenueByPlan->sum('revenue');

            $target = Target::where('user_id', $user->id)->value('closure_target');

            $revenueTarget = Target::where('user_id', $user->id)->value('revenue_targets');

            $meetingTarget = Target::where('user_id', $user->id)->value('daily_meeting_targets');

            $meeting = Meeting::whereDate('created_at', '=', Carbon::today())
                ->where('user_id', $user->id)
                ->count();            

            return response()->json([
                'total leads' => $totalLeads,
                'sold'        => $sold,
                'follow ups'  => $followUps,
                'target'      => $target ?? 0,
                'revenue'     => $revenue ?? 0,
                'revenue target' => $revenueTarget ?? 0,
                'meeting target' => $meetingTarget ?? 0,
                'meeting'     => $meeting ?? 0,
                'revenueByPlan' => $revenueByPlan ?? 0,
            ]);
        } catch (\Throwable $e) {
            Log::error($e);
            return response()->json([
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage(),                
            ], 500);
        }
    }

    public function dailyActivity(Request $request)
    {
        try {
            $user = $request->user();

            $today = Carbon::today();
            $tomorrow = Carbon::tomorrow();

            $newLeads = Lead::whereBetween('created_at', [$today, $tomorrow])
                ->where('assigned_to', $user->id)
                ->count();

            $completedLeads = Lead::whereBetween('completed_at', [$today, $tomorrow])
                ->where('assigned_to', $user->id)
                ->count();

            $followUps = Lead::whereDate('next_follow_up_date', '=', $today)
                ->where('assigned_to', $user->id)
                ->count();

            return response()->json([
                'new leads today' => $newLeads,
                'completed leads today' => $completedLeads,
                'follow ups today' => $followUps,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to fetch daily activity',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
