<?php

namespace Tests\Feature;

use App\Models\Finding;
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
            . '</head><body><h1>Hello</h1></body></html>';

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

        // All 15 checks should pass with the well-formed test HTML
        $passedCount = $site->findings()->where('status', 'passed')->count();
        $this->assertEquals(15, $passedCount, "Expected 15 passed findings, got {$passedCount}");
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
}
