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
        $this->checkImageAltText($site, $html, $results);
        $this->checkOpenGraph($site, $html, $results);
        $this->checkHttpStatusCode($site, $status, $results);
        $this->checkCompression($site, $url, $results);
        $this->checkTextToHtmlRatio($site, $html, $results);

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

    private function checkImageAltText(Site $site, string $html, array &$results): void
    {
        preg_match_all('/<img[^>]*>/is', $html, $imgMatches);
        $totalImages = count($imgMatches[0]);
        $missingAlt = 0;

        foreach ($imgMatches[0] as $imgTag) {
            if (!preg_match('/\salt=["\'][^"\']*["\']/is', $imgTag) && !preg_match('/\salt\s*=/is', $imgTag)) {
                $missingAlt++;
            }
        }

        $results['images_total'] = $totalImages;
        $results['images_missing_alt'] = $missingAlt;

        if ($totalImages === 0) {
            $this->createPassedFinding($site, 'image_alt_text', 'No images found on the page');
        } elseif ($missingAlt > 0) {
            $this->createFinding($site, 'image_alt_text', "{$missingAlt} of {$totalImages} images are missing alt text", 'medium');
        } else {
            $this->createPassedFinding($site, 'image_alt_text', "All {$totalImages} images have alt text");
        }
    }

    private function checkOpenGraph(Site $site, string $html, array &$results): void
    {
        $hasOgTitle = (bool) preg_match('/<meta[^>]+property=["\']og:title["\']/is', $html);
        $hasOgDesc = (bool) preg_match('/<meta[^>]+property=["\']og:description["\']/is', $html);
        $hasOgImage = (bool) preg_match('/<meta[^>]+property=["\']og:image["\']/is', $html);

        $results['has_og_title'] = $hasOgTitle;
        $results['has_og_description'] = $hasOgDesc;
        $results['has_og_image'] = $hasOgImage;

        $missing = [];
        if (!$hasOgTitle) $missing[] = 'og:title';
        if (!$hasOgDesc) $missing[] = 'og:description';
        if (!$hasOgImage) $missing[] = 'og:image';

        if (count($missing) > 0) {
            $this->createFinding($site, 'open_graph', 'Missing OpenGraph tags: ' . implode(', ', $missing), count($missing) >= 2 ? 'medium' : 'low');
        } else {
            $this->createPassedFinding($site, 'open_graph', 'All recommended OpenGraph tags are present');
        }
    }

    private function checkHttpStatusCode(Site $site, int $status, array &$results): void
    {
        $results['http_status'] = $status;

        if ($status >= 400) {
            $this->createFinding($site, 'http_status', "Page returned HTTP {$status} error", 'high');
        } elseif ($status >= 300) {
            $this->createFinding($site, 'http_status', "Page returned HTTP {$status} redirect", 'medium');
        } else {
            $this->createPassedFinding($site, 'http_status', "Page returned HTTP {$status}");
        }
    }

    private function checkCompression(Site $site, string $url, array &$results): void
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['Accept-Encoding' => 'gzip, deflate, br'])
                ->get($url);
            $encoding = $response->header('Content-Encoding');
            $hasCompression = !empty($encoding);
        } catch (\Exception $e) {
            $hasCompression = false;
            $encoding = null;
        }

        $results['has_compression'] = $hasCompression;
        $results['compression_type'] = $encoding;

        if (!$hasCompression) {
            $this->createFinding($site, 'compression', 'No compression detected — enable gzip or Brotli to reduce page size', 'medium');
        } else {
            $this->createPassedFinding($site, 'compression', "Compression is enabled ({$encoding})");
        }
    }

    private function checkTextToHtmlRatio(Site $site, string $html, array &$results): void
    {
        $textContent = strip_tags($html);
        $textContent = preg_replace('/\s+/', ' ', $textContent);
        $textLen = strlen(trim($textContent));
        $htmlLen = strlen($html);

        $ratio = $htmlLen > 0 ? round(($textLen / $htmlLen) * 100, 1) : 0;
        $results['text_to_html_ratio'] = $ratio;

        if ($ratio < 10) {
            $this->createFinding($site, 'text_html_ratio', "Text-to-HTML ratio is {$ratio}% (recommended: above 10%)", 'low');
        } else {
            $this->createPassedFinding($site, 'text_html_ratio', "Text-to-HTML ratio is {$ratio}%");
        }
    }

    /**
     * Lightweight scan of any URL — returns ['check' => bool] without creating findings.
     * Used for competitor scanning.
     */
    public function scanUrl(string $url): array
    {
        $url = rtrim($url, '/');
        $checks = [];

        try {
            $start = microtime(true);
            $response = Http::timeout(15)->get($url);
            $ttfb = round((microtime(true) - $start) * 1000);
            $html = $response->body();
            $status = $response->status();
        } catch (\Exception $e) {
            return ['error' => 'Could not reach site: ' . $e->getMessage()];
        }

        // Title
        preg_match('/<title>(.*?)<\/title>/is', $html, $m);
        $titleText = trim($m[1] ?? '');
        $checks['title'] = !empty($titleText);

        // Store site name (title text) as metadata — not a boolean check
        $checks['_site_name'] = $titleText ?: null;

        // Meta description
        preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/is', $html, $m);
        $checks['meta_description'] = !empty(trim($m[1] ?? ''));

        // H1
        preg_match_all('/<h1[^>]*>(.*?)<\/h1>/is', $html, $matches);
        $checks['h1'] = count($matches[1]) === 1;

        // TTFB
        $checks['ttfb'] = $ttfb <= 800;

        // HTTPS
        $checks['https'] = str_starts_with($url, 'https://');

        // Indexability
        $noindex = (bool) preg_match('/<meta[^>]+name=["\']robots["\'][^>]+content=["\'][^"\']*noindex/is', $html);
        $checks['indexability'] = $status === 200 && !$noindex;

        // Viewport
        $checks['viewport'] = (bool) preg_match('/<meta[^>]+name=["\']viewport["\']/is', $html);

        // Canonical
        preg_match('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\'](.*?)["\']/is', $html, $m);
        $checks['canonical'] = !empty(trim($m[1] ?? ''));

        // Lang attribute
        $checks['lang_attribute'] = (bool) preg_match('/<html[^>]+lang=["\']([^"\']+)["\']/is', $html);

        // Charset
        $checks['charset'] = (bool) preg_match('/<meta[^>]+charset=["\'"]?[^"\'>]+/is', $html)
            || (bool) preg_match('/<meta[^>]+http-equiv=["\']Content-Type["\']/is', $html);

        // Analytics
        $checks['analytics'] = (bool) preg_match('/google-analytics\.com|googletagmanager\.com|gtag\(|analytics\.js|ga\.js|plausible\.io|umami/is', $html);

        // GSC
        $checks['gsc_verification'] = (bool) preg_match('/<meta[^>]+name=["\']google-site-verification["\']/is', $html);

        // Structured data
        $checks['structured_data'] = (bool) preg_match('/<script[^>]+type=["\']application\/ld\+json["\']/is', $html);

        // XML sitemap
        try {
            $sitemapResponse = Http::timeout(10)->get($url . '/sitemap.xml');
            $checks['xml_sitemap'] = $sitemapResponse->ok() && str_contains($sitemapResponse->body(), '<urlset');
        } catch (\Exception $e) {
            $checks['xml_sitemap'] = false;
        }

        // robots.txt
        try {
            $robotsResponse = Http::timeout(10)->get($url . '/robots.txt');
            $checks['robots_txt'] = $robotsResponse->ok() && strlen(trim($robotsResponse->body())) > 0;
        } catch (\Exception $e) {
            $checks['robots_txt'] = false;
        }

        // GBP
        $hasGbpLink = (bool) preg_match('/(?:href|src)=["\'][^"\']*(?:google\.com\/maps|maps\.google\.com|business\.google\.com|goo\.gl\/maps|maps\.app\.goo\.gl)/is', $html);
        if (!$hasGbpLink) {
            $hasGbpLink = (bool) preg_match('/<iframe[^>]+src=["\'][^"\']*google\.com\/maps\/embed/is', $html);
        }
        $checks['google_business_profile'] = $hasGbpLink;

        // Image alt text
        preg_match_all('/<img[^>]*>/is', $html, $imgMatches);
        $missingAlt = 0;
        foreach ($imgMatches[0] as $imgTag) {
            if (!preg_match('/\salt=["\'][^"\']*["\']/is', $imgTag) && !preg_match('/\salt\s*=/is', $imgTag)) {
                $missingAlt++;
            }
        }
        $checks['image_alt_text'] = $missingAlt === 0;

        // OpenGraph
        $checks['open_graph'] = (bool) preg_match('/<meta[^>]+property=["\']og:title["\']/is', $html)
            && (bool) preg_match('/<meta[^>]+property=["\']og:description["\']/is', $html)
            && (bool) preg_match('/<meta[^>]+property=["\']og:image["\']/is', $html);

        // HTTP status code
        $checks['http_status'] = $status >= 200 && $status < 300;

        // Compression
        try {
            $compResponse = Http::timeout(10)->withHeaders(['Accept-Encoding' => 'gzip, deflate, br'])->get($url);
            $checks['compression'] = !empty($compResponse->header('Content-Encoding'));
        } catch (\Exception $e) {
            $checks['compression'] = false;
        }

        // Text-to-HTML ratio
        $textContent = strip_tags($html);
        $textContent = preg_replace('/\s+/', ' ', $textContent);
        $textLen = strlen(trim($textContent));
        $htmlLen = strlen($html);
        $checks['text_html_ratio'] = $htmlLen > 0 ? ($textLen / $htmlLen) * 100 >= 10 : false;

        // Extract site_name before returning (not a check, just metadata)
        $siteName = $checks['_site_name'] ?? null;
        unset($checks['_site_name']);

        return ['checks' => $checks, 'site_name' => $siteName];
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
            'image_alt_text'  => 'Add alt text to all images for accessibility and SEO',
            'open_graph'      => 'Add OpenGraph tags (og:title, og:description, og:image) for social sharing',
            'http_status'     => 'Fix the HTTP status code to return 200',
            'compression'     => 'Enable gzip or Brotli compression on your server',
            'text_html_ratio' => 'Increase the text-to-HTML ratio by adding more content or reducing code bloat',
            default           => 'Review and fix: ' . $slug,
        };
    }
}

