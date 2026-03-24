<?php

namespace App\Services;

use App\Models\Site;
use App\Models\SerpResult;
use Illuminate\Support\Facades\Http;

class SerpService
{
    /**
     * Check all tracked keywords for a site.
     *
     * @return SerpResult[]
     */
    public function checkAll(Site $site): array
    {
        $keywords = $site->keywords;
        $results = [];

        foreach ($keywords as $kw) {
            $result = $this->checkKeyword($site, $kw->phrase);
            if ($result) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Check the site's ranking for a specific keyword.
     */
    public function checkKeyword(Site $site, string $keyword): ?SerpResult
    {
        $organic = $this->search($keyword);
        if ($organic === null) {
            return null;
        }

        $siteDomain = $this->extractDomain($site->url);
        $position = null;
        $resultUrl = null;
        $snippet = null;

        foreach ($organic as $result) {
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
            'total_results' => count($organic),
        ]);
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

