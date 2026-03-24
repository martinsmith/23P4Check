<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sites = $request->user()
            ->sites()
            ->withCount(['findings as open_findings_count' => fn ($q) => $q->where('status', 'open')])
            ->orderByDesc('updated_at')
            ->get();

        return response()->json(['sites' => $sites]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url'  => 'required|url',
            'name' => 'nullable|string|max:255',
        ]);

        $site = $request->user()->sites()->create($data);

        return response()->json(['site' => $site], 201);
    }

    public function show(Request $request, Site $site): JsonResponse
    {
        abort_unless($site->user_id === $request->user()->id, 403);

        $site->load(['findings' => fn ($q) => $q->whereIn('status', ['open', 'passed'])
            ->orderByRaw("CASE status WHEN 'open' THEN 0 WHEN 'passed' THEN 1 ELSE 2 END")
            ->orderByRaw("CASE severity WHEN 'high' THEN 0 WHEN 'medium' THEN 1 WHEN 'low' THEN 2 ELSE 3 END")
            ->with('tasks'), 'competitors']);

        return response()->json(['site' => $site]);
    }

    public function update(Request $request, Site $site): JsonResponse
    {
        abort_unless($site->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name'          => 'nullable|string|max:255',
            'business_type' => 'nullable|string|max:255',
            'location'      => 'nullable|string|max:255',
            'service_area'  => 'nullable|string|max:255',
            'competitors'   => 'nullable|array|max:5',
            'competitors.*' => 'required|string|max:255',
        ]);

        $competitors = $data['competitors'] ?? null;
        unset($data['competitors']);

        $site->update($data);

        if ($competitors !== null) {
            $site->competitors()->delete();
            foreach ($competitors as $domain) {
                $site->competitors()->create(['domain' => trim($domain)]);
            }
        }

        $site->load('competitors');

        return response()->json(['site' => $site]);
    }

    public function destroy(Request $request, Site $site): JsonResponse
    {
        abort_unless($site->user_id === $request->user()->id, 403);

        $site->delete();

        return response()->json(null, 204);
    }
}

