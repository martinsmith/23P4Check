<?php

namespace App\Services;

use App\Models\Site;

class MissionGenerator
{
    /**
     * Generate missions for a site based on its business context and scan findings.
     * Idempotent — uses slug-based upsert so existing missions aren't duplicated.
     */
    public function generate(Site $site): void
    {
        $site->load(['findings', 'competitors']);

        foreach ($this->templates() as $template) {
            if (!$this->shouldApply($template, $site)) {
                continue;
            }

            $mission = $site->missions()->updateOrCreate(
                ['slug' => $template['slug']],
                [
                    'title'       => $this->interpolate($template['title'], $site),
                    'description' => $this->interpolate($template['description'], $site),
                    'category'    => $template['category'],
                    'type'        => $template['type'],
                    'priority'    => $template['priority'],
                ],
            );

            // Only create steps if the mission was just created (no steps yet)
            if ($mission->steps()->count() === 0) {
                foreach ($template['steps'] as $i => $step) {
                    $mission->steps()->create([
                        'description' => $this->interpolate($step, $site),
                        'sort'        => $i,
                    ]);
                }
            }
        }

        // Remove missions whose conditions are no longer met (e.g. finding was fixed)
        $activeSlugs = collect($this->templates())
            ->filter(fn ($t) => $this->shouldApply($t, $site))
            ->pluck('slug')
            ->toArray();

        $site->missions()
            ->where('status', 'pending')
            ->whereNotIn('slug', $activeSlugs)
            ->delete();
    }

    private function shouldApply(array $template, Site $site): bool
    {
        $conditions = $template['conditions'];

        // Check business context requirements
        if (($conditions['needs_business_type'] ?? false) && empty($site->business_type)) {
            return false;
        }
        if (($conditions['needs_location'] ?? false) && empty($site->location)) {
            return false;
        }
        if (($conditions['needs_service_area'] ?? false) && empty($site->service_area)) {
            return false;
        }
        if (($conditions['needs_competitors'] ?? false) && $site->competitors->isEmpty()) {
            return false;
        }

        // Check finding conditions
        if ($slug = ($conditions['finding_open'] ?? null)) {
            $hasOpen = $site->findings->contains(fn ($f) => $f->check === $slug && $f->status === 'open');
            if (!$hasOpen) {
                return false;
            }
        }
        if ($slug = ($conditions['finding_passed'] ?? null)) {
            $hasPassed = $site->findings->contains(fn ($f) => $f->check === $slug && $f->status === 'passed');
            if (!$hasPassed) {
                return false;
            }
        }

        return true;
    }

    private function interpolate(string $text, Site $site): string
    {
        return str_replace(
            ['{business_type}', '{location}', '{service_area}'],
            [$site->business_type ?? '', $site->location ?? '', $site->service_area ?? ''],
            $text,
        );
    }

    /**
     * The template library — reactive + proactive missions.
     */
    private function templates(): array
    {
        return [
            // ── Reactive: from open findings + business context ──

            [
                'slug'        => 'add-local-structured-data',
                'title'       => 'Add LocalBusiness structured data',
                'description' => 'Add JSON-LD structured data that tells Google you are a {business_type} in {location}. This enables rich results and improves local visibility.',
                'category'    => 'local_seo',
                'type'        => 'reactive',
                'priority'    => 1,
                'conditions'  => ['finding_open' => 'structured_data', 'needs_business_type' => true, 'needs_location' => true],
                'steps'       => [
                    'Choose the correct Schema.org type for your business (e.g. LocalBusiness, Plumber, Restaurant)',
                    'Add a JSON-LD script block to your homepage with your business name, address, and phone number',
                    'Include your opening hours and service area',
                    'Test the markup using Google\'s Rich Results Test tool',
                ],
            ],

            [
                'slug'        => 'optimise-title-for-local',
                'title'       => 'Optimise your title tag for {location}',
                'description' => 'Your title tag should include your business type and location so search engines know where you operate.',
                'category'    => 'content',
                'type'        => 'reactive',
                'priority'    => 1,
                'conditions'  => ['finding_open' => 'title', 'needs_business_type' => true, 'needs_location' => true],
                'steps'       => [
                    'Write a title that includes "{business_type}" and "{location}" (e.g. "{business_type} in {location} | Your Company Name")',
                    'Keep the title under 60 characters',
                    'Update the title tag in your page\'s HTML <head>',
                ],
            ],

            [
                'slug'        => 'write-local-meta-description',
                'title'       => 'Write a meta description targeting {location}',
                'description' => 'Your meta description is missing. Write one that mentions your services and location to improve click-through rates.',
                'category'    => 'content',
                'type'        => 'reactive',
                'priority'    => 2,
                'conditions'  => ['finding_open' => 'meta_description', 'needs_location' => true],
                'steps'       => [
                    'Write a compelling description that includes "{business_type}" and "{location}"',
                    'Keep it under 160 characters',
                    'Include a call to action (e.g. "Call today", "Get a free quote")',
                    'Add the meta description tag to your page\'s HTML <head>',
                ],
            ],

            [
                'slug'        => 'install-analytics-tracking',
                'title'       => 'Install analytics to track your visitors',
                'description' => 'Without analytics, you cannot measure whether your growth efforts are working. Set up tracking so you can see what\'s happening.',
                'category'    => 'tracking',
                'type'        => 'reactive',
                'priority'    => 1,
                'conditions'  => ['finding_open' => 'analytics'],
                'steps'       => [
                    'Create a Google Analytics 4 property (or choose an alternative like Plausible/Fathom)',
                    'Add the tracking script to every page of your site',
                    'Verify data is flowing by visiting your site and checking the real-time report',
                ],
            ],

            [
                'slug'        => 'create-xml-sitemap',
                'title'       => 'Create and submit an XML sitemap',
                'description' => 'An XML sitemap helps search engines discover all your pages faster.',
                'category'    => 'technical',
                'type'        => 'reactive',
                'priority'    => 2,
                'conditions'  => ['finding_open' => 'xml_sitemap'],
                'steps'       => [
                    'Generate a sitemap.xml file (use a plugin or online generator)',
                    'Upload it to the root of your website (e.g. yoursite.com/sitemap.xml)',
                    'Submit the sitemap URL in Google Search Console',
                ],
            ],

            // ── Proactive: from business context alone ──

            [
                'slug'        => 'claim-google-business-profile',
                'title'       => 'Claim your Google Business Profile',
                'description' => 'A Google Business Profile is essential for appearing in local search results and Google Maps for {location}.',
                'category'    => 'local_seo',
                'type'        => 'reactive',
                'priority'    => 1,
                'conditions'  => ['finding_open' => 'google_business_profile', 'needs_business_type' => true, 'needs_location' => true],
                'steps'       => [
                    'Go to business.google.com and search for your business',
                    'Claim or create your listing with your exact business name and address',
                    'Complete all profile fields: hours, phone, website, services, description',
                    'Add at least 5 high-quality photos of your business',
                    'Verify your listing (Google will send a postcard or call)',
                ],
            ],

            [
                'slug'        => 'create-service-area-page',
                'title'       => 'Create a dedicated page for {service_area}',
                'description' => 'A page specifically about your service area helps you rank for "{business_type} in {service_area}" searches.',
                'category'    => 'content',
                'type'        => 'proactive',
                'priority'    => 2,
                'conditions'  => ['needs_business_type' => true, 'needs_service_area' => true],
                'steps'       => [
                    'Create a new page on your site focused on {service_area}',
                    'Write 300-500 words about the services you provide in {service_area}',
                    'Include a clear heading with "{business_type} in {service_area}"',
                    'Add a call-to-action for visitors in that area',
                    'Link to this page from your homepage and navigation',
                ],
            ],

            [
                'slug'        => 'set-up-review-generation',
                'title'       => 'Start collecting customer reviews',
                'description' => 'Reviews are a major ranking factor for local search. Set up a process to consistently collect reviews from happy customers.',
                'category'    => 'local_seo',
                'type'        => 'proactive',
                'priority'    => 2,
                'conditions'  => ['needs_business_type' => true, 'needs_location' => true],
                'steps'       => [
                    'Find your Google Business Profile review link (share button on your profile)',
                    'Create a short URL or QR code that links directly to your review page',
                    'Ask your 5 most recent happy customers to leave a review',
                    'Set up a routine: after every completed job, send a follow-up with the review link',
                ],
            ],

            [
                'slug'        => 'analyse-competitor-content',
                'title'       => 'Review what your competitors are doing well',
                'description' => 'Look at your competitors\' websites to identify content, features, and trust signals you should adopt.',
                'category'    => 'content',
                'type'        => 'proactive',
                'priority'    => 3,
                'conditions'  => ['needs_competitors' => true],
                'steps'       => [
                    'Visit each competitor\'s homepage and note what services they highlight',
                    'Check if they have customer testimonials or case studies',
                    'Look at their page titles and meta descriptions - note the keywords they use',
                    'Identify 3 things they do that your site doesn\'t - plan to add them',
                ],
            ],
        ];

    }
}
