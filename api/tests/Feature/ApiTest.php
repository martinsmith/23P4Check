<?php

namespace Tests\Feature;

use App\Models\Finding;
use App\Models\Mission;
use App\Models\MissionStep;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // --- Auth ---

    public function test_login_with_valid_credentials(): void
    {
        config(['sanctum.stateful' => ['localhost']]);

        $response = $this->withHeaders(['Referer' => 'http://localhost'])
            ->postJson('/api/login', [
                'email' => $this->user->email,
                'password' => 'password',
            ]);

        $response->assertOk()->assertJsonPath('user.email', $this->user->email);
    }

    public function test_login_with_invalid_credentials(): void
    {
        config(['sanctum.stateful' => ['localhost']]);

        $response = $this->withHeaders(['Referer' => 'http://localhost'])
            ->postJson('/api/login', [
                'email' => $this->user->email,
                'password' => 'wrong',
            ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_access_api(): void
    {
        $this->getJson('/api/user')->assertStatus(401);
        $this->getJson('/api/sites')->assertStatus(401);
    }

    // --- Sites CRUD ---

    public function test_can_list_sites(): void
    {
        Site::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/sites');

        $response->assertOk()
            ->assertJsonCount(3, 'sites');
    }

    public function test_cannot_see_other_users_sites(): void
    {
        $otherUser = User::factory()->create();
        Site::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/sites');

        $response->assertOk()->assertJsonCount(0, 'sites');
    }

    public function test_can_create_site(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/sites', ['url' => 'https://example.com']);

        $response->assertStatus(201)
            ->assertJsonPath('site.url', 'https://example.com');

        $this->assertDatabaseHas('sites', [
            'user_id' => $this->user->id,
            'url' => 'https://example.com',
        ]);
    }

    public function test_create_site_validates_url(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/sites', ['url' => 'not-a-url']);

        $response->assertStatus(422);
    }

    public function test_can_show_own_site(): void
    {
        $site = Site::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/sites/{$site->id}");

        $response->assertOk()->assertJsonPath('site.id', $site->id);
    }

    public function test_cannot_show_other_users_site(): void
    {
        $site = Site::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/sites/{$site->id}");

        $response->assertStatus(403);
    }

    public function test_can_delete_own_site(): void
    {
        $site = Site::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/sites/{$site->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('sites', ['id' => $site->id]);
    }

    // --- Update Site (Business Context) ---

    public function test_can_update_business_context(): void
    {
        $site = Site::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/sites/{$site->id}", [
                'business_type' => 'Plumber',
                'location' => 'Manchester, UK',
                'service_area' => 'Greater Manchester',
                'competitors' => ['rival1.com', 'rival2.com'],
            ]);

        $response->assertOk()
            ->assertJsonPath('site.business_type', 'Plumber')
            ->assertJsonPath('site.location', 'Manchester, UK');

        $this->assertDatabaseHas('sites', [
            'id' => $site->id,
            'business_type' => 'Plumber',
            'location' => 'Manchester, UK',
            'service_area' => 'Greater Manchester',
        ]);

        $this->assertEquals(2, $site->competitors()->count());
        $this->assertDatabaseHas('competitors', ['site_id' => $site->id, 'domain' => 'rival1.com']);
    }

    public function test_competitors_limited_to_five(): void
    {
        $site = Site::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/sites/{$site->id}", [
                'competitors' => ['a.com', 'b.com', 'c.com', 'd.com', 'e.com', 'f.com'],
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_update_other_users_site(): void
    {
        $site = Site::factory()->create();

        $response = $this->actingAs($this->user)
            ->putJson("/api/sites/{$site->id}", ['business_type' => 'Test']);

        $response->assertStatus(403);
    }

    public function test_show_includes_competitors(): void
    {
        $site = Site::factory()->create(['user_id' => $this->user->id]);
        $site->competitors()->create(['domain' => 'example-rival.com']);

        $response = $this->actingAs($this->user)
            ->getJson("/api/sites/{$site->id}");

        $response->assertOk()
            ->assertJsonPath('site.competitors.0.domain', 'example-rival.com');
    }

    // --- Scan ---

    public function test_can_scan_site(): void
    {
        $html = '<html lang="en"><head>'
            . '<meta charset="utf-8">'
            . '<title>Test</title>'
            . '<meta name="description" content="A test page">'
            . '<meta name="viewport" content="width=device-width">'
            . '<link rel="canonical" href="https://example.com">'
            . '<meta name="google-site-verification" content="abc123">'
            . '<script type="application/ld+json">{"@type":"Organization"}</script>'
            . '<script src="https://www.googletagmanager.com/gtag/js"></script>'
            . '</head><body><h1>Hello</h1>'
            . '<a href="https://google.com/maps/place/Example">Find us on Google Maps</a>'
            . '</body></html>';

        Http::fake([
            'https://example.com' => Http::response($html, 200),
            'https://example.com/sitemap.xml' => Http::response('<?xml version="1.0"?><urlset></urlset>', 200),
            'https://example.com/robots.txt' => Http::response("User-agent: *\nAllow: /", 200),
        ]);

        $site = Site::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/sites/{$site->id}/scan");

        $response->assertOk()
            ->assertJsonStructure(['site', 'results']);

        $site->refresh();
        $this->assertNotNull($site->last_scanned_at);

        // All 16 checks should pass with the well-formed test HTML
        $passedCount = $site->findings()->where('status', 'passed')->count();
        $this->assertEquals(16, $passedCount, "Expected 16 passed findings, got {$passedCount}");
        $this->assertEquals(0, $site->findings()->where('status', 'open')->count());
    }

    // --- Complete Finding ---

    public function test_can_complete_finding(): void
    {
        $site = Site::factory()->create(['user_id' => $this->user->id]);
        $finding = Finding::factory()->create([
            'site_id' => $site->id,
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/sites/{$site->id}/findings/{$finding->id}/complete");

        $response->assertOk()
            ->assertJsonPath('finding.status', 'fixed');
    }

    // --- Missions ---

    public function test_can_generate_missions_with_business_context_and_findings(): void
    {
        $site = Site::factory()->create([
            'user_id' => $this->user->id,
            'business_type' => 'Plumber',
            'location' => 'Manchester, UK',
            'service_area' => 'Greater Manchester',
        ]);

        // Create open findings that should trigger reactive missions
        Finding::factory()->create([
            'site_id' => $site->id,
            'check' => 'structured_data',
            'status' => 'open',
        ]);
        Finding::factory()->create([
            'site_id' => $site->id,
            'check' => 'google_business_profile',
            'status' => 'open',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/sites/{$site->id}/missions/generate");

        $response->assertOk()
            ->assertJsonStructure(['missions']);

        $missions = $response->json('missions');
        $this->assertNotEmpty($missions);

        // Should include reactive missions triggered by open findings
        $slugs = collect($missions)->pluck('slug')->toArray();
        $this->assertContains('add-local-structured-data', $slugs);
        $this->assertContains('claim-google-business-profile', $slugs);
    }

    public function test_missions_are_idempotent(): void
    {
        $site = Site::factory()->create([
            'user_id' => $this->user->id,
            'business_type' => 'Plumber',
            'location' => 'Manchester, UK',
        ]);

        // Generate twice
        $this->actingAs($this->user)
            ->postJson("/api/sites/{$site->id}/missions/generate");
        $response = $this->actingAs($this->user)
            ->postJson("/api/sites/{$site->id}/missions/generate");

        $response->assertOk();

        // No duplicates — count unique slugs should equal total count
        $missions = $response->json('missions');
        $slugs = collect($missions)->pluck('slug');
        $this->assertEquals($slugs->count(), $slugs->unique()->count());
    }

    public function test_can_list_missions(): void
    {
        $site = Site::factory()->create([
            'user_id' => $this->user->id,
            'business_type' => 'Plumber',
            'location' => 'Manchester, UK',
        ]);

        // Generate missions first
        $this->actingAs($this->user)
            ->postJson("/api/sites/{$site->id}/missions/generate");

        $response = $this->actingAs($this->user)
            ->getJson("/api/sites/{$site->id}/missions");

        $response->assertOk()
            ->assertJsonStructure(['missions' => [['id', 'slug', 'title', 'steps']]]);
    }

    public function test_can_toggle_step_completion(): void
    {
        $site = Site::factory()->create([
            'user_id' => $this->user->id,
            'business_type' => 'Plumber',
            'location' => 'Manchester, UK',
        ]);

        // Generate missions
        $this->actingAs($this->user)
            ->postJson("/api/sites/{$site->id}/missions/generate");

        $mission = $site->missions()->first();
        $step = $mission->steps()->first();

        // Toggle on
        $response = $this->actingAs($this->user)
            ->postJson("/api/sites/{$site->id}/missions/{$mission->id}/steps/{$step->id}/toggle");

        $response->assertOk();
        $this->assertTrue($response->json('mission.steps.0.completed'));

        // Toggle off
        $response = $this->actingAs($this->user)
            ->postJson("/api/sites/{$site->id}/missions/{$mission->id}/steps/{$step->id}/toggle");

        $response->assertOk();
        $this->assertFalse($response->json('mission.steps.0.completed'));
    }

    public function test_mission_status_updates_on_step_completion(): void
    {
        $site = Site::factory()->create([
            'user_id' => $this->user->id,
            'business_type' => 'Plumber',
            'location' => 'Manchester, UK',
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/sites/{$site->id}/missions/generate");

        $mission = $site->missions()->first();
        $steps = $mission->steps;

        // Complete all steps
        foreach ($steps as $step) {
            $this->actingAs($this->user)
                ->postJson("/api/sites/{$site->id}/missions/{$mission->id}/steps/{$step->id}/toggle");
        }

        $mission->refresh();
        $this->assertEquals('completed', $mission->status);
    }

    public function test_cannot_access_other_users_missions(): void
    {
        $otherUser = User::factory()->create();
        $site = Site::factory()->create([
            'user_id' => $otherUser->id,
            'business_type' => 'Plumber',
            'location' => 'Manchester, UK',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/sites/{$site->id}/missions");

        $response->assertStatus(403);
    }

    public function test_reactive_missions_require_open_findings(): void
    {
        $site = Site::factory()->create([
            'user_id' => $this->user->id,
            'business_type' => 'Plumber',
            'location' => 'Manchester, UK',
        ]);

        // No open findings — reactive missions should not appear
        $response = $this->actingAs($this->user)
            ->postJson("/api/sites/{$site->id}/missions/generate");

        $slugs = collect($response->json('missions'))->pluck('slug')->toArray();
        $this->assertNotContains('add-local-structured-data', $slugs);
        $this->assertNotContains('optimise-title-for-local', $slugs);
        $this->assertNotContains('claim-google-business-profile', $slugs);

        // Proactive missions should still appear (only need business context)
        $this->assertContains('set-up-review-generation', $slugs);
    }

    public function test_no_missions_without_business_context(): void
    {
        $site = Site::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/sites/{$site->id}/missions/generate");

        $response->assertOk();
        $this->assertEmpty($response->json('missions'));
    }
}
