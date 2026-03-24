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

        $site->load(['findings' => fn ($q) => $q->where('status', 'open')->with('tasks')]);

        return response()->json(['site' => $site]);
    }

    public function destroy(Request $request, Site $site): JsonResponse
    {
        abort_unless($site->user_id === $request->user()->id, 403);

        $site->delete();

        return response()->json(null, 204);
    }
}

