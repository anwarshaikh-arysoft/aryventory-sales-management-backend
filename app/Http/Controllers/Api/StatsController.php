<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\Target;
use Carbon\Carbon;

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

            $followUps = Lead::whereDate('next_follow_up_date', '<=', Carbon::today())
                ->where('assigned_to', $user->id)
                ->count();

            $target = Target::where('user_id', $user->id)->value('closure_target');

            return response()->json([
                'total leads' => $totalLeads,
                'sold'        => $sold,
                'follow ups'  => $followUps,
                'target'      => $target ?? 0,
            ]);
        } catch (\Throwable $e) {
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
