<?php

namespace App\Http\Controllers;

use App\Models\Finding;
use App\Models\Site;
use App\Services\Scanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function store(Request $request, Site $site, Scanner $scanner): JsonResponse
    {
        abort_unless($site->user_id === $request->user()->id, 403);

        $results = $scanner->scan($site);

        $site->load(['findings' => fn ($q) => $q->whereIn('status', ['open', 'passed'])
            ->orderByRaw("CASE status WHEN 'open' THEN 0 WHEN 'passed' THEN 1 ELSE 2 END")
            ->orderByRaw("CASE severity WHEN 'high' THEN 0 WHEN 'medium' THEN 1 WHEN 'low' THEN 2 ELSE 3 END")
            ->with('tasks')]);

        return response()->json([
            'site'    => $site,
            'results' => $results,
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

