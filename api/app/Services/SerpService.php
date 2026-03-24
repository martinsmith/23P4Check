<?php

namespace App\Services;

use App\Models\Site;
use App\Models\SerpResult;
use Illuminate\Support\Facades\Http;

class SerpService
{
    /**
     * Check the site's ranking for its primary keyword.
     * Primary keyword = "{business_type} in {location}"
     */
    public function check(Site $site): ?SerpResult
    {
        $keyword = $this->buildKeyword($site);
        if (!$keyword) {
            return null;
        }

        $results = $this->search($keyword);
        if ($results === null) {
            return null;
        }

        // Find the site's position in the results
        $siteDomain = $this->extractDomain($site->url);
        $position = null;
        $resultUrl = null;
        $snippet = null;

        foreach ($results as $result) {
            $resultDomain = $this->extractDomain($result['link'] ?? '');
            if ($resultDomain && $siteDomain && str_contains($resultDomain, $siteDomain)) {
                $position = $result['position'] ?? null;
                $resultUrl = $result['link'] ?? null;
                $snippet = $result['snippet'] ?? null;
                break;
            }
        }

        return $site->serpResults()->create([
            'keyword'       => $keyword,
            'position'      => $position,
            'result_url'    => $resultUrl,
            'snippet'       => $snippet,
            'total_results' => count($results),
        ]);
    }

    /**
     * Build the primary keyword from business context.
     */
    public function buildKeyword(Site $site): ?string
    {
        if (empty($site->business_type) || empty($site->location)) {
            return null;
        }

        return $site->business_type . ' in ' . $site->location;
    }

    /**
     * Call the Serper API and return organic results.
     */
    public function search(string $query): ?array
    {
        $apiKey = config('services.serper.api_key');
        if (!$apiKey) {
            return null;
        }

        $response = Http::withHeaders([
            'X-API-KEY'    => $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://google.serper.dev/search', [
            'q'   => $query,
            'gl'  => 'gb',
            'num' => 100,
        ]);

        if (!$response->successful()) {
            return null;
        }

        return $response->json('organic', []);
    }

    /**
     * Extract the root domain from a URL.
     */
    private function extractDomain(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return null;
        }

        // Remove www. prefix for matching
        return preg_replace('/^www\./', '', $host);
    }
}

