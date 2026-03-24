<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function show(Site $site): JsonResponse
    {
        abort_unless($site->user_id === auth()->id(), 403);

        // Visibility score: % of checks passing
        $passed = $site->findings()->where('status', 'passed')->count();
        $open   = $site->findings()->where('status', 'open')->count();
        $total  = $passed + $open;
        $visibilityScore = $total > 0 ? round(($passed / $total) * 100) : 0;

        // Mission stats
        $totalMissions    = $site->missions()->count();
        $completedMissions = $site->missions()->where('status', 'completed')->count();
        $totalSteps       = 0;
        $completedSteps   = 0;

        $site->load('missions.steps');
        foreach ($site->missions as $mission) {
            $totalSteps += $mission->steps->count();
            $completedSteps += $mission->steps->where('completed', true)->count();
        }

        $missionPct = $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100) : 0;

        // Scan trend — last 20 snapshots
        $snapshots = $site->scanSnapshots()
            ->orderBy('created_at')
            ->limit(20)
            ->get(['passed_count', 'failed_count', 'total_checks', 'created_at'])
            ->map(fn ($s) => [
                'passed'  => $s->passed_count,
                'failed'  => $s->failed_count,
                'total'   => $s->total_checks,
                'score'   => $s->total_checks > 0 ? round(($s->passed_count / $s->total_checks) * 100) : 0,
                'date'    => $s->created_at->toDateTimeString(),
            ]);

        return response()->json([
            'visibility_score' => $visibilityScore,
            'checks'           => ['passed' => $passed, 'failed' => $open, 'total' => $total],
            'missions'         => [
                'total'     => $totalMissions,
                'completed' => $completedMissions,
                'steps'     => ['total' => $totalSteps, 'completed' => $completedSteps],
                'pct'       => $missionPct,
            ],
            'trend' => $snapshots,
        ]);
    }
}

