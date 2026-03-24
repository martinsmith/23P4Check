<?php

namespace App\Http\Controllers;

use App\Models\Finding;
use App\Models\ScanSnapshot;
use App\Models\Site;
use App\Services\MissionGenerator;
use App\Services\Scanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function store(Request $request, Site $site, Scanner $scanner, MissionGenerator $missionGenerator): JsonResponse
    {
        abort_unless($site->user_id === $request->user()->id, 403);

        $results = $scanner->scan($site);

        // Record scan snapshot for trend tracking
        $site->loadCount([
            'findings as passed_count' => fn ($q) => $q->where('status', 'passed'),
            'findings as open_count'   => fn ($q) => $q->where('status', 'open'),
        ]);

        ScanSnapshot::create([
            'site_id'      => $site->id,
            'passed_count' => $site->passed_count,
            'failed_count' => $site->open_count,
            'total_checks' => $site->passed_count + $site->open_count,
        ]);

        // Auto-regenerate missions so reactive ones refresh based on new findings
        if ($site->business_type) {
            $missionGenerator->generate($site);
        }

        $site->load(['findings' => fn ($q) => $q->whereIn('status', ['open', 'passed'])
            ->orderByRaw("CASE status WHEN 'open' THEN 0 WHEN 'passed' THEN 1 ELSE 2 END")
            ->orderByRaw("CASE severity WHEN 'high' THEN 0 WHEN 'medium' THEN 1 WHEN 'low' THEN 2 ELSE 3 END")
            ->with('tasks')]);

        // Include missions in response so frontend can update in one round-trip
        $site->load(['missions' => fn ($q) => $q->with('steps')->orderBy('priority')]);

        return response()->json([
            'site'     => $site,
            'results'  => $results,
            'missions' => $site->missions,
        ]);
    }

    public function completeFinding(Request $request, Site $site, Finding $finding): JsonResponse
    {
        abort_unless($site->user_id === $request->user()->id, 403);
        abort_unless($finding->site_id === $site->id, 404);

        $finding->update(['status' => 'fixed']);

        return response()->json(['finding' => $finding->fresh()]);
    }

}

