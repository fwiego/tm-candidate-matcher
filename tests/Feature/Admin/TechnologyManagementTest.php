<?php

namespace Tests\Feature\Admin;

use App\Models\Candidate;
use App\Models\JobRequest;
use App\Models\Requirement;
use App\Models\Role;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnologyManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;

    protected Role $managerRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['slug' => 'admin', 'name' => 'Админ']);
        $this->managerRole = Role::create(['slug' => 'manager', 'name' => 'Менеджер']);
    }

    protected function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach($this->adminRole);

        return $admin;
    }

    protected function manager(): User
    {
        $manager = User::factory()->create();
        $manager->roles()->attach($this->managerRole);

        return $manager;
    }

    public function test_admin_can_view_technologies_list(): void
    {
        Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        $this->actingAs($this->admin())
            ->get(route('admin.technologies.index'))
            ->assertOk();
    }

    public function test_non_admin_cannot_view_technologies_list(): void
    {
        $this->actingAs($this->manager())
            ->get(route('admin.technologies.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_technologies_list(): void
    {
        $this->get(route('admin.technologies.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_create_technology_with_synonyms(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.technologies.store'), [
            'name' => 'JavaScript',
            'group' => 'Frontend',
            'synonyms' => ['JS', 'ECMAScript'],
        ]);

        $response->assertRedirect(route('admin.technologies.index'));

        $this->assertDatabaseHas('technologies', ['name' => 'JavaScript', 'group' => 'Frontend']);

        $tech = Technology::where('name', 'JavaScript')->first();
        $this->assertSame(['JS', 'ECMAScript'], $tech->synonyms);
    }

    public function test_create_technology_strips_empty_synonym_entries(): void
    {
        $this->actingAs($this->admin())->post(route('admin.technologies.store'), [
            'name' => 'Go',
            'synonyms' => ['Golang', '', '   '],
        ]);

        $tech = Technology::where('name', 'Go')->first();
        $this->assertSame(['Golang'], $tech->synonyms);
    }

    public function test_non_admin_cannot_create_technology(): void
    {
        $this->actingAs($this->manager())->post(route('admin.technologies.store'), [
            'name' => 'Rust',
        ])->assertForbidden();

        $this->assertDatabaseMissing('technologies', ['name' => 'Rust']);
    }

    public function test_create_technology_requires_unique_name(): void
    {
        Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        $this->actingAs($this->admin())->post(route('admin.technologies.store'), [
            'name' => 'PHP',
        ])->assertSessionHasErrors(['name']);
    }

    public function test_create_technology_requires_name(): void
    {
        $this->actingAs($this->admin())->post(route('admin.technologies.store'), [
            'name' => '',
        ])->assertSessionHasErrors(['name']);
    }

    public function test_admin_can_update_technology(): void
    {
        $tech = Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        $response = $this->actingAs($this->admin())->put(route('admin.technologies.update', $tech), [
            'name' => 'PHP 8',
            'group' => 'Backend',
            'synonyms' => ['PHP8'],
        ]);

        $response->assertRedirect(route('admin.technologies.index'));

        $tech->refresh();
        $this->assertSame('PHP 8', $tech->name);
        $this->assertSame(['PHP8'], $tech->synonyms);
    }

    public function test_update_technology_can_keep_same_name(): void
    {
        $tech = Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        $this->actingAs($this->admin())->put(route('admin.technologies.update', $tech), [
            'name' => 'PHP',
            'group' => 'Backend updated',
        ])->assertRedirect(route('admin.technologies.index'));

        $this->assertSame('Backend updated', $tech->fresh()->group);
    }

    public function test_admin_can_delete_unused_technology(): void
    {
        $tech = Technology::create(['name' => 'Unused Tech']);

        $this->actingAs($this->admin())
            ->delete(route('admin.technologies.destroy', $tech))
            ->assertRedirect(route('admin.technologies.index'));

        $this->assertDatabaseMissing('technologies', ['id' => $tech->id]);
    }

    public function test_admin_cannot_delete_technology_used_in_requirement(): void
    {
        $admin = $this->admin();
        $tech = Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        $request = JobRequest::create([
            'position' => 'Backend Developer',
            'grade' => 'middle',
            'status' => 'open',
            'created_by' => $admin->id,
        ]);

        Requirement::create([
            'request_id' => $request->id,
            'technology_id' => $tech->id,
            'type' => 'must',
            'weight' => 5,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.technologies.destroy', $tech))
            ->assertRedirect();

        $this->assertDatabaseHas('technologies', ['id' => $tech->id]);
    }

    public function test_admin_cannot_delete_technology_used_by_candidate(): void
    {
        $admin = $this->admin();
        $tech = Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        $candidate = Candidate::create([
            'full_name' => 'Jane Doe',
            'file_path' => 'resumes/jane.pdf',
            'uploaded_by' => $admin->id,
        ]);
        $candidate->skills()->attach($tech->id);

        $this->actingAs($admin)
            ->delete(route('admin.technologies.destroy', $tech))
            ->assertRedirect();

        $this->assertDatabaseHas('technologies', ['id' => $tech->id]);
    }

    public function test_non_admin_cannot_delete_technology(): void
    {
        $tech = Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        $this->actingAs($this->manager())
            ->delete(route('admin.technologies.destroy', $tech))
            ->assertForbidden();

        $this->assertDatabaseHas('technologies', ['id' => $tech->id]);
    }
}