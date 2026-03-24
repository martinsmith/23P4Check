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
        $this->checkIndexability($site, $html, $status, $results);
        $this->checkViewport($site, $html, $results);
        $this->checkCanonical($site, $html, $results);
        $this->checkLanguageAttribute($site, $html, $results);
        $this->checkCharacterEncoding($site, $html, $results);
        $this->checkAnalytics($site, $html, $results);
        $this->checkGoogleSearchConsole($site, $html, $results);
        $this->checkStructuredData($site, $html, $results);
        $this->checkXmlSitemap($site, $url, $results);
        $this->checkRobotsTxt($site, $url, $results);
        $this->checkGoogleBusinessProfile($site, $html, $results);

        $site->update(['last_scanned_at' => now()]);

        return $results;
    }

    private function checkTitle(Site $site, string $html, array &$results): void
    {
        preg_match('/<title>(.*?)<\/title>/is', $html, $m);
        $title = trim($m[1] ?? '');
        $results['title'] = $title;

        if (empty($title)) {
            $this->createFinding($site, 'title', 'Page has no title tag', 'high');
        } elseif (mb_strlen($title) > 60) {
            $this->createFinding($site, 'title', "Title is " . mb_strlen($title) . " chars (recommended: under 60)", 'medium');
        } else {
            $this->createPassedFinding($site, 'title', 'Title tag is present and within recommended length');
        }
    }

    private function checkMetaDescription(Site $site, string $html, array &$results): void
    {
        preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/is', $html, $m);
        $desc = trim($m[1] ?? '');
        $results['meta_description'] = $desc;

        if (empty($desc)) {
            $this->createFinding($site, 'meta_description', 'Page has no meta description', 'high');
        } elseif (mb_strlen($desc) > 160) {
            $this->createFinding($site, 'meta_description', "Meta description is " . mb_strlen($desc) . " chars (recommended: under 160)", 'low');
        } else {
            $this->createPassedFinding($site, 'meta_description', 'Meta description is present and within recommended length');
        }
    }

    private function checkH1(Site $site, string $html, array &$results): void
    {
        preg_match_all('/<h1[^>]*>(.*?)<\/h1>/is', $html, $matches);
        $count = count($matches[1]);
        $results['h1_count'] = $count;
        $results['h1_text'] = $matches[1][0] ?? null;

        if ($count === 0) {
            $this->createFinding($site, 'h1', 'Page has no H1 heading', 'high');
        } elseif ($count > 1) {
            $this->createFinding($site, 'h1', "Page has {$count} H1 headings (recommended: 1)", 'medium');
        } else {
            $this->createPassedFinding($site, 'h1', 'Page has a single H1 heading');
        }
    }

    private function checkTtfb(Site $site, int $ttfb, array &$results): void
    {
        if ($ttfb > 800) {
            $this->createFinding($site, 'ttfb', "TTFB is {$ttfb}ms (should be under 800ms)", 'high');
        } elseif ($ttfb > 400) {
            $this->createFinding($site, 'ttfb', "TTFB is {$ttfb}ms (could be faster)", 'medium');
        } else {
            $this->createPassedFinding($site, 'ttfb', "TTFB is {$ttfb}ms — fast response time");
        }
    }

    private function checkHttps(Site $site, string $url, array &$results): void
    {
        $results['is_https'] = str_starts_with($url, 'https://');

        if (!$results['is_https']) {
            $this->createFinding($site, 'https', 'Site does not use HTTPS', 'high');
        } else {
            $this->createPassedFinding($site, 'https', 'Site uses HTTPS');
        }
    }

    private function checkIndexability(Site $site, string $html, int $status, array &$results): void
    {
        $noindex = (bool) preg_match('/<meta[^>]+name=["\']robots["\'][^>]+content=["\'][^"\']*noindex/is', $html);
        $results['indexable'] = $status === 200 && !$noindex;

        if ($status !== 200) {
            $this->createFinding($site, 'indexability', "Homepage returned HTTP {$status} — search engines may not index it", 'high');
        } elseif ($noindex) {
            $this->createFinding($site, 'indexability', 'Homepage has a noindex directive — search engines will not index it', 'high');
        } else {
            $this->createPassedFinding($site, 'indexability', 'Homepage is indexable — HTTP 200 with no noindex directive');
        }
    }

    private function checkViewport(Site $site, string $html, array &$results): void
    {
        $hasViewport = (bool) preg_match('/<meta[^>]+name=["\']viewport["\']/is', $html);
        $results['has_viewport'] = $hasViewport;

        if (!$hasViewport) {
            $this->createFinding($site, 'viewport', 'Page has no viewport meta tag — it may not render correctly on mobile devices', 'high');
        } else {
            $this->createPassedFinding($site, 'viewport', 'Viewport meta tag is present');
        }
    }

    private function checkCanonical(Site $site, string $html, array &$results): void
    {
        preg_match('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\'](.*?)["\']/is', $html, $m);
        $canonical = trim($m[1] ?? '');
        $results['canonical'] = $canonical ?: null;

        if (empty($canonical)) {
            $this->createFinding($site, 'canonical', 'Page has no canonical URL — this can cause duplicate content issues', 'medium');
        } else {
            $this->createPassedFinding($site, 'canonical', 'Canonical URL is specified');
        }
    }

    private function checkLanguageAttribute(Site $site, string $html, array &$results): void
    {
        $hasLang = (bool) preg_match('/<html[^>]+lang=["\']([^"\']+)["\']/is', $html);
        $results['has_lang'] = $hasLang;

        if (!$hasLang) {
            $this->createFinding($site, 'lang_attribute', 'Page does not declare a language attribute on the <html> tag', 'medium');
        } else {
            $this->createPassedFinding($site, 'lang_attribute', 'Language attribute is declared');
        }
    }

    private function checkCharacterEncoding(Site $site, string $html, array &$results): void
    {
        $hasCharset = (bool) preg_match('/<meta[^>]+charset=["\'"]?[^"\'>]+/is', $html)
            || (bool) preg_match('/<meta[^>]+http-equiv=["\']Content-Type["\']/is', $html);
        $results['has_charset'] = $hasCharset;

        if (!$hasCharset) {
            $this->createFinding($site, 'charset', 'Page does not declare character encoding — text may not render correctly', 'medium');
        } else {
            $this->createPassedFinding($site, 'charset', 'Character encoding is declared');
        }
    }

    private function checkAnalytics(Site $site, string $html, array &$results): void
    {
        $hasAnalytics = (bool) preg_match('/google-analytics\.com|googletagmanager\.com|gtag\(|analytics\.js|ga\.js|plausible\.io|umami/is', $html);
        $results['has_analytics'] = $hasAnalytics;

        if (!$hasAnalytics) {
            $this->createFinding($site, 'analytics', 'No analytics tracking detected — you cannot measure traffic or SEO impact', 'medium');
        } else {
            $this->createPassedFinding($site, 'analytics', 'Analytics tracking is installed');
        }
    }

    private function checkGoogleSearchConsole(Site $site, string $html, array &$results): void
    {
        $hasGsc = (bool) preg_match('/<meta[^>]+name=["\']google-site-verification["\']/is', $html);
        $results['has_gsc_verification'] = $hasGsc;

        if (!$hasGsc) {
            $this->createFinding($site, 'gsc_verification', 'No Google Search Console verification tag found', 'low');
        } else {
            $this->createPassedFinding($site, 'gsc_verification', 'Google Search Console verification tag found');
        }
    }

    private function checkStructuredData(Site $site, string $html, array &$results): void
    {
        $hasJsonLd = (bool) preg_match('/<script[^>]+type=["\']application\/ld\+json["\']/is', $html);
        $results['has_structured_data'] = $hasJsonLd;

        if (!$hasJsonLd) {
            $this->createFinding($site, 'structured_data', 'No JSON-LD structured data found — your site misses out on rich search results', 'medium');
        } else {
            $this->createPassedFinding($site, 'structured_data', 'JSON-LD structured data found');
        }
    }

    private function checkXmlSitemap(Site $site, string $url, array &$results): void
    {
        try {
            $response = Http::timeout(10)->get($url . '/sitemap.xml');
            $hasSitemap = $response->ok() && str_contains($response->body(), '<urlset');
        } catch (\Exception $e) {
            $hasSitemap = false;
        }

        $results['has_xml_sitemap'] = $hasSitemap;

        if (!$hasSitemap) {
            $this->createFinding($site, 'xml_sitemap', 'No XML sitemap found — search engines must rely on crawling links alone', 'medium');
        } else {
            $this->createPassedFinding($site, 'xml_sitemap', 'XML sitemap found');
        }
    }

    private function checkRobotsTxt(Site $site, string $url, array &$results): void
    {
        try {
            $response = Http::timeout(10)->get($url . '/robots.txt');
            $hasRobots = $response->ok() && strlen(trim($response->body())) > 0;
        } catch (\Exception $e) {
            $hasRobots = false;
        }

        $results['has_robots_txt'] = $hasRobots;

        if (!$hasRobots) {
            $this->createFinding($site, 'robots_txt', 'No robots.txt file found — search engines have no crawl directives', 'low');
        } else {
            $this->createPassedFinding($site, 'robots_txt', 'robots.txt file found');
        }
    }

    private function checkGoogleBusinessProfile(Site $site, string $html, array &$results): void
    {
        // Check for links to GBP/Google Maps (all known URL formats)
        $hasGbpLink = (bool) preg_match('/(?:href|src)=["\'][^"\']*(?:google\.com\/maps|maps\.google\.com|business\.google\.com|goo\.gl\/maps|maps\.app\.goo\.gl)/is', $html);

        // Also check for embedded Google Maps iframes
        if (!$hasGbpLink) {
            $hasGbpLink = (bool) preg_match('/<iframe[^>]+src=["\'][^"\']*google\.com\/maps\/embed/is', $html);
        }

        $results['has_google_business_profile'] = $hasGbpLink;

        if (!$hasGbpLink) {
            $this->createFinding($site, 'google_business_profile', 'Your site does not link to a Google Business Profile — add a link or embed a Google Map so local customers can find you', 'medium');
        } else {
            $this->createPassedFinding($site, 'google_business_profile', 'Google Business Profile or Maps link found on the page');
        }
    }

    private function createFinding(Site $site, string $slug, string $description, string $severity): void
    {
        // Remove any previous passed finding for this check
        $site->findings()->where('check', $slug)->where('status', 'passed')->delete();

        $finding = $site->findings()->updateOrCreate(
            ['check' => $slug, 'status' => 'open'],
            ['message' => $description, 'severity' => $severity],
        );

        if ($finding->tasks()->count() === 0) {
            $finding->tasks()->create([
                'description' => $this->taskTitleFor($slug),
                'sort'        => 0,
            ]);
        }
    }

    private function createPassedFinding(Site $site, string $slug, string $description): void
    {
        // Remove any previous open finding for this check
        $site->findings()->where('check', $slug)->where('status', 'open')->delete();

        $site->findings()->updateOrCreate(
            ['check' => $slug, 'status' => 'passed'],
            ['message' => $description, 'severity' => 'low'],
        );
    }

    private function taskTitleFor(string $slug): string
    {
        return match ($slug) {
            'title'           => 'Fix the title tag',
            'meta_description'=> 'Fix the meta description',
            'h1'              => 'Fix the H1 heading',
            'ttfb'            => 'Investigate and optimise server response time',
            'https'           => 'Enable HTTPS on the site',
            'indexability'    => 'Ensure the homepage is indexable',
            'viewport'        => 'Add a viewport meta tag for mobile compatibility',
            'canonical'       => 'Add a canonical URL to avoid duplicate content issues',
            'lang_attribute'  => 'Add a lang attribute to the <html> tag',
            'charset'         => 'Declare character encoding with a charset meta tag',
            'analytics'       => 'Install analytics tracking (e.g. Google Analytics, Plausible)',
            'gsc_verification'=> 'Add Google Search Console verification to your site',
            'structured_data' => 'Add JSON-LD structured data for rich search results',
            'xml_sitemap'     => 'Create and submit an XML sitemap',
            'robots_txt'      => 'Add a robots.txt file with crawl directives',
            'google_business_profile' => 'Create a Google Business Profile and link to it from your site',
            default           => 'Review and fix: ' . $slug,
        };
    }
}

