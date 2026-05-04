<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSiteManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Site $site1;
    protected Site $site2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create sites
        $this->site1 = Site::create([
            'name' => 'Site 1',
            'domain' => 'site1.test',
            'is_active' => true,
        ]);

        $this->site2 = Site::create([
            'name' => 'Site 2',
            'domain' => 'site2.test',
            'is_active' => true,
        ]);

        // Create admin user
        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    public function test_it_can_list_pages_for_specific_site(): void
    {
        // Create pages for different sites
        Page::create([
            'site_id' => $this->site1->id,
            'title' => 'Page Site 1',
            'slug' => 'page-site-1',
            'is_published' => true,
        ]);

        Page::create([
            'site_id' => $this->site2->id,
            'title' => 'Page Site 2',
            'slug' => 'page-site-2',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/pages?site_id={$this->site1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Page Site 1')
            ->assertJsonPath('site_id', $this->site1->id);
    }

    public function test_it_defaults_to_site_id_1_when_not_specified(): void
    {
        // Create page for site_id = 1
        Page::create([
            'site_id' => 1,
            'title' => 'Default Site Page',
            'slug' => 'default-site-page',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/pages');

        $response->assertStatus(200)
            ->assertJsonPath('site_id', 1);
    }

    public function test_it_can_create_page_with_site_id(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/pages', [
                'site_id' => $this->site1->id,
                'title' => 'New Page',
                'is_published' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.site_id', $this->site1->id)
            ->assertJsonPath('data.title', 'New Page');

        $this->assertDatabaseHas('pages', [
            'site_id' => $this->site1->id,
            'title' => 'New Page',
        ]);
    }

    public function test_it_validates_site_exists_on_create(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/pages', [
                'site_id' => 999, // Non-existent site
                'title' => 'New Page',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['site_id']);
    }

    public function test_it_can_show_page_with_site_verification(): void
    {
        $page = Page::create([
            'site_id' => $this->site1->id,
            'title' => 'Test Page',
            'slug' => 'test-page',
            'is_published' => true,
        ]);

        // Should succeed when requesting with correct site_id
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/pages/{$page->id}?site_id={$this->site1->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $page->id);
    }

    public function test_it_returns_404_when_page_not_in_requested_site(): void
    {
        $page = Page::create([
            'site_id' => $this->site1->id,
            'title' => 'Test Page',
            'slug' => 'test-page',
            'is_published' => true,
        ]);

        // Should fail when requesting with different site_id
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/pages/{$page->id}?site_id={$this->site2->id}");

        $response->assertStatus(404);
    }

    public function test_it_can_update_page_with_site_id(): void
    {
        $page = Page::create([
            'site_id' => $this->site1->id,
            'title' => 'Original Title',
            'slug' => 'original-title',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/v1/pages/{$page->id}?site_id={$this->site1->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_it_can_delete_page_with_site_verification(): void
    {
        $page = Page::create([
            'site_id' => $this->site1->id,
            'title' => 'Page to Delete',
            'slug' => 'page-to-delete',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/pages/{$page->id}?site_id={$this->site1->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Page moved to trash');

        $this->assertSoftDeleted('pages', [
            'id' => $page->id,
        ]);
    }

    public function test_page_resource_includes_site_id_and_name_when_loaded(): void
    {
        $page = Page::create([
            'site_id' => $this->site1->id,
            'title' => 'Test Page',
            'slug' => 'test-page',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/pages/{$page->id}?site_id={$this->site1->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.site_id', $this->site1->id);
    }
}
