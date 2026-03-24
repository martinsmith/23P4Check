<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\MissionStep;
use App\Models\Site;
use App\Services\MissionGenerator;
use Illuminate\Http\JsonResponse;

class MissionController extends Controller
{
    /**
     * Generate missions for a site based on its business context + findings.
     */
    public function generate(Site $site, MissionGenerator $generator): JsonResponse
    {
        if ($site->user_id !== auth()->id()) {
            abort(403);
        }

        $generator->generate($site);

        return $this->listMissions($site);
    }

    /**
     * List all missions for a site (with steps).
     */
    public function index(Site $site): JsonResponse
    {
        if ($site->user_id !== auth()->id()) {
            abort(403);
        }

        return $this->listMissions($site);
    }

    /**
     * Complete (or uncomplete) a mission step.
     */
    public function completeStep(Site $site, Mission $mission, MissionStep $step): JsonResponse
    {
        if ($site->user_id !== auth()->id()) {
            abort(403);
        }

        // Ensure the step belongs to this mission and site
        if ($mission->site_id !== $site->id || $step->mission_id !== $mission->id) {
            abort(404);
        }

        $step->update(['completed' => !$step->completed]);

        // Auto-update mission status based on step completion
        $mission->load('steps');
        $allDone = $mission->steps->every(fn ($s) => $s->completed);
        $anyDone = $mission->steps->contains(fn ($s) => $s->completed);

        $mission->update([
            'status' => $allDone ? 'completed' : ($anyDone ? 'in_progress' : 'pending'),
        ]);

        return response()->json([
            'mission' => $mission->load('steps'),
        ]);
    }

    private function listMissions(Site $site): JsonResponse
    {
        $missions = $site->missions()
            ->with('steps')
            ->orderBy('priority')
            ->orderBy('created_at')
            ->get();

        return response()->json(['missions' => $missions]);
    }
}

