<?php

namespace App\Services;

use App\Models\Site;
use Illuminate\Support\Facades\Http;

class Scanner
{
    public function scan(Site $site): array
    {
        $url = rtrim($site->url, '/');
        $results = [];

        try {
            $start = microtime(true);
            $response = Http::timeout(15)->get($url);
            $ttfb = round((microtime(true) - $start) * 1000);
            $html = $response->body();
            $status = $response->status();
        } catch (\Exception $e) {
            $site->update(['last_scanned_at' => now()]);
            return ['error' => 'Could not reach site: ' . $e->getMessage()];
        }

        $results['status'] = $status;
        $results['ttfb_ms'] = $ttfb;

        $this->checkTitle($site, $html, $results);
        $this->checkMetaDescription($site, $html, $results);
        $this->checkH1($site, $html, $results);
        $this->checkTtfb($site, $ttfb, $results);
        $this->checkHttps($site, $url, $results);

        $site->update(['last_scanned_at' => now()]);

        return $results;
    }

    private function checkTitle(Site $site, string $html, array &$results): void
    {
        preg_match('/<title>(.*?)<\/title>/is', $html, $m);
        $title = trim($m[1] ?? '');
        $results['title'] = $title;

        if (empty($title)) {
            $this->createFinding($site, 'missing_title', 'Page has no title tag', 'high');
        } elseif (mb_strlen($title) > 60) {
            $this->createFinding($site, 'long_title', "Title is " . mb_strlen($title) . " chars (recommended: under 60)", 'medium');
        }
    }

    private function checkMetaDescription(Site $site, string $html, array &$results): void
    {
        preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/is', $html, $m);
        $desc = trim($m[1] ?? '');
        $results['meta_description'] = $desc;

        if (empty($desc)) {
            $this->createFinding($site, 'missing_meta_desc', 'Page has no meta description', 'high');
        } elseif (mb_strlen($desc) > 160) {
            $this->createFinding($site, 'long_meta_desc', "Meta description is " . mb_strlen($desc) . " chars (recommended: under 160)", 'low');
        }
    }

    private function checkH1(Site $site, string $html, array &$results): void
    {
        preg_match_all('/<h1[^>]*>(.*?)<\/h1>/is', $html, $matches);
        $count = count($matches[1]);
        $results['h1_count'] = $count;
        $results['h1_text'] = $matches[1][0] ?? null;

        if ($count === 0) {
            $this->createFinding($site, 'missing_h1', 'Page has no H1 heading', 'high');
        } elseif ($count > 1) {
            $this->createFinding($site, 'multiple_h1', "Page has {$count} H1 headings (recommended: 1)", 'medium');
        }
    }

    private function checkTtfb(Site $site, int $ttfb, array &$results): void
    {
        if ($ttfb > 800) {
            $this->createFinding($site, 'slow_ttfb', "TTFB is {$ttfb}ms (should be under 800ms)", 'high');
        } elseif ($ttfb > 400) {
            $this->createFinding($site, 'moderate_ttfb', "TTFB is {$ttfb}ms (could be faster)", 'medium');
        }
    }

    private function checkHttps(Site $site, string $url, array &$results): void
    {
        $results['is_https'] = str_starts_with($url, 'https://');

        if (!$results['is_https']) {
            $this->createFinding($site, 'no_https', 'Site does not use HTTPS', 'high');
        }
    }

    private function createFinding(Site $site, string $slug, string $description, string $severity): void
    {
        $existing = $site->findings()
            ->where('check', $slug)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return;
        }

        $finding = $site->findings()->create([
            'check'    => $slug,
            'message'  => $description,
            'severity' => $severity,
            'status'   => 'open',
        ]);

        $finding->tasks()->create([
            'description' => $this->taskTitleFor($slug),
            'sort'        => 0,
        ]);
    }

    private function taskTitleFor(string $slug): string
    {
        return match ($slug) {
            'missing_title'    => 'Add a descriptive title tag',
            'long_title'       => 'Shorten the title to under 60 characters',
            'missing_meta_desc'=> 'Write a meta description',
            'long_meta_desc'   => 'Shorten meta description to under 160 characters',
            'missing_h1'       => 'Add an H1 heading to the page',
            'multiple_h1'      => 'Reduce to a single H1 heading',
            'slow_ttfb'        => 'Investigate server response time',
            'moderate_ttfb'    => 'Consider optimising server response time',
            'no_https'         => 'Enable HTTPS on the site',
            default            => 'Review and fix: ' . $slug,
        };
    }
}

