<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use App\Models\Site;
use App\Services\SerpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SerpController extends Controller
{
    /**
     * GET /sites/{site}/serp/keywords
     * List all tracked keywords for this site.
     */
    public function keywords(Request $request, Site $site): JsonResponse
    {
        abort_unless($site->user_id === $request->user()->id, 403);

        $keywords = $site->keywords()->orderBy('created_at')->get()->map(fn ($k) => [
            'id'    => $k->id,
            'phrase' => $k->phrase,
        ]);

        return response()->json(['keywords' => $keywords]);
    }

    /**
     * POST /sites/{site}/serp/keywords
     * Add a keyword to track. Max 5 per site.
     */
    public function storeKeyword(Request $request, Site $site): JsonResponse
    {
        abort_unless($site->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'phrase' => 'required|string|max:255',
        ]);

        if ($site->keywords()->count() >= 5) {
            return response()->json([
                'error' => 'Maximum of 5 keywords allowed per site.',
            ], 422);
        }

        // Prevent duplicates
        $exists = $site->keywords()->where('phrase', $data['phrase'])->exists();
        if ($exists) {
            return response()->json([
                'error' => 'This keyword is already being tracked.',
            ], 422);
        }

        $keyword = $site->keywords()->create(['phrase' => $data['phrase']]);

        return response()->json([
            'keyword' => ['id' => $keyword->id, 'phrase' => $keyword->phrase],
        ], 201);
    }

    /**
     * DELETE /sites/{site}/serp/keywords/{keyword}
     * Remove a tracked keyword.
     */
    public function destroyKeyword(Request $request, Site $site, Keyword $keyword): JsonResponse
    {
        abort_unless($site->user_id === $request->user()->id, 403);
        abort_unless($keyword->site_id === $site->id, 404);

        $keyword->delete();

        return response()->json(null, 204);
    }

    /**
     * POST /sites/{site}/serp/check
     * Trigger a SERP check for all tracked keywords.
     */
    public function check(Request $request, Site $site, SerpService $serpService): JsonResponse
    {
        abort_unless($site->user_id === $request->user()->id, 403);

        $keywords = $site->keywords;

        if ($keywords->isEmpty()) {
            return response()->json([
                'error' => 'No keywords to track. Add at least one keyword first.',
            ], 422);
        }

        $results = $serpService->checkAll($site);

        if (empty($results)) {
            return response()->json([
                'error' => 'Could not check rankings. Please try again later.',
            ], 500);
        }

        return response()->json([
            'results' => collect($results)->map(fn ($r) => [
                'id'            => $r->id,
                'keyword'       => $r->keyword,
                'position'      => $r->position,
                'result_url'    => $r->result_url,
                'snippet'       => $r->snippet,
                'total_results' => $r->total_results,
                'checked_at'    => $r->created_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * GET /sites/{site}/serp/history
     * Return SERP results grouped by keyword, newest first.
     */
    public function history(Request $request, Site $site): JsonResponse
    {
        abort_unless($site->user_id === $request->user()->id, 403);

        $keywords = $site->keywords()->orderBy('created_at')->get()->map(fn ($k) => [
            'id'    => $k->id,
            'phrase' => $k->phrase,
        ]);

        $results = $site->serpResults()
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn ($r) => [
                'id'            => $r->id,
                'keyword'       => $r->keyword,
                'position'      => $r->position,
                'result_url'    => $r->result_url,
                'snippet'       => $r->snippet,
                'total_results' => $r->total_results,
                'checked_at'    => $r->created_at->toIso8601String(),
            ]);

        return response()->json([
            'keywords' => $keywords,
            'history'  => $results,
        ]);
    }
}

