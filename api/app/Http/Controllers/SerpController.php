<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\SerpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SerpController extends Controller
{
    /**
     * POST /sites/{site}/serp/check
     * Trigger a SERP check for the site's primary keyword.
     */
    public function check(Request $request, Site $site, SerpService $serpService): JsonResponse
    {
        abort_unless($site->user_id === $request->user()->id, 403);

        if (empty($site->business_type) || empty($site->location)) {
            return response()->json([
                'error' => 'Business type and location are required. Set them in the Growth Plan tab.',
            ], 422);
        }

        $result = $serpService->check($site);

        if (!$result) {
            return response()->json([
                'error' => 'Could not check rankings. Please try again later.',
            ], 500);
        }

        return response()->json([
            'result' => [
                'id'            => $result->id,
                'keyword'       => $result->keyword,
                'position'      => $result->position,
                'result_url'    => $result->result_url,
                'snippet'       => $result->snippet,
                'total_results' => $result->total_results,
                'checked_at'    => $result->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /sites/{site}/serp/history
     * Return all SERP results for the site, newest first.
     */
    public function history(Request $request, Site $site): JsonResponse
    {
        abort_unless($site->user_id === $request->user()->id, 403);

        $keyword = (new SerpService)->buildKeyword($site);

        $results = $site->serpResults()
            ->orderByDesc('created_at')
            ->limit(50)
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
            'keyword' => $keyword,
            'history' => $results,
        ]);
    }
}

