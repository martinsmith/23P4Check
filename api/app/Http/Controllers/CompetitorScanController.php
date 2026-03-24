<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\CompetitorScan;
use App\Services\Scanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompetitorScanController extends Controller
{
    /**
     * POST /sites/{site}/competitors/scan
     * Scan all competitors for the given site.
     */
    public function scan(Request $request, Site $site, Scanner $scanner): JsonResponse
    {
        abort_unless($site->user_id === $request->user()->id, 403);

        $competitors = $site->competitors;

        if ($competitors->isEmpty()) {
            return response()->json(['error' => 'No competitors configured'], 422);
        }

        $results = [];

        foreach ($competitors as $competitor) {
            $domain = $competitor->domain;
            $url = str_starts_with($domain, 'http') ? $domain : 'https://' . $domain;

            $scanResult = $scanner->scanUrl($url);

            if (isset($scanResult['error'])) {
                $results[] = [
                    'competitor_id' => $competitor->id,
                    'domain' => $domain,
                    'error' => $scanResult['error'],
                ];
                continue;
            }

            $checks = $scanResult['checks'];
            $siteName = $scanResult['site_name'] ?? null;
            $passed = count(array_filter($checks));
            $total = count($checks);

            $scan = CompetitorScan::updateOrCreate(
                ['competitor_id' => $competitor->id, 'site_id' => $site->id],
                [
                    'business_name' => $siteName,
                    'results' => $checks,
                    'passed_count' => $passed,
                    'failed_count' => $total - $passed,
                    'total_checks' => $total,
                ],
            );

            $results[] = [
                'competitor_id' => $competitor->id,
                'domain' => $domain,
                'business_name' => $siteName,
                'passed' => $passed,
                'failed' => $total - $passed,
                'total' => $total,
            ];
        }

        return response()->json(['scans' => $results]);
    }

    /**
     * GET /sites/{site}/competitors/results
     * Return latest scan results for site + all competitors, side by side.
     */
    public function results(Request $request, Site $site, Scanner $scanner): JsonResponse
    {
        abort_unless($site->user_id === $request->user()->id, 403);

        // Use scanUrl for the user's own site so the comparison is apples-to-apples
        // (findings track nuanced issues like "title too long" as open, but scanUrl
        // uses the same binary pass/fail logic as competitor scans)
        $scanResult = $scanner->scanUrl($site->url);
        if (isset($scanResult['error'])) {
            // Fall back to findings if the site can't be reached right now
            $findings = $site->findings()->get();
            $ownChecks = [];
            foreach ($findings as $f) {
                $ownChecks[$f->check] = $f->status === 'passed';
            }
        } else {
            $ownChecks = $scanResult['checks'];
        }
        $ownPassed = count(array_filter($ownChecks));
        $ownTotal = count($ownChecks);

        // Build competitor results
        $competitorResults = [];
        foreach ($site->competitors as $competitor) {
            $scan = CompetitorScan::where('competitor_id', $competitor->id)
                ->where('site_id', $site->id)
                ->latest()
                ->first();

            $competitorResults[] = [
                'competitor_id' => $competitor->id,
                'domain' => $competitor->domain,
                'business_name' => $scan?->business_name ?? null,
                'results' => $scan?->results ?? null,
                'passed' => $scan?->passed_count ?? null,
                'failed' => $scan?->failed_count ?? null,
                'total' => $scan?->total_checks ?? null,
                'scanned_at' => $scan?->updated_at?->toIso8601String(),
            ];
        }

        return response()->json([
            'own' => [
                'results' => $ownChecks,
                'passed' => $ownPassed,
                'failed' => $ownTotal - $ownPassed,
                'total' => $ownTotal,
            ],
            'competitors' => $competitorResults,
        ]);
    }
}

