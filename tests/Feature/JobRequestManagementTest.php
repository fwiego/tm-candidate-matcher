<?php

namespace Tests\Feature;

use App\Models\JobRequest;
use App\Models\Role;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;

    protected Role $managerRole;

    protected Role $supervisorRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['slug' => 'admin', 'name' => 'Админ']);
        $this->managerRole = Role::create(['slug' => 'manager', 'name' => 'Менеджер']);
        $this->supervisorRole = Role::create(['slug' => 'supervisor', 'name' => 'Руководитель']);
    }

    protected function admin(): User
    {
        $u = User::factory()->create();
        $u->roles()->attach($this->adminRole);

        return $u;
    }

    protected function manager(): User
    {
        $u = User::factory()->create();
        $u->roles()->attach($this->managerRole);

        return $u;
    }

    protected function supervisor(): User
    {
        $u = User::factory()->create();
        $u->roles()->attach($this->supervisorRole);

        return $u;
    }

    public function test_manager_can_view_requests_list(): void
    {
        $this->actingAs($this->manager())
            ->get(route('requests.index'))
            ->assertOk();
    }

    public function test_supervisor_can_view_requests_list(): void
    {
        $this->actingAs($this->supervisor())
            ->get(route('requests.index'))
            ->assertOk();
    }

    public function test_guest_is_redirected_from_requests_list(): void
    {
        $this->get(route('requests.index'))
            ->assertRedirect(route('login'));
    }

    public function test_manager_can_create_draft_request_without_requirements(): void
    {
        $manager = $this->manager();

        $response = $this->actingAs($manager)->post(route('requests.store'), [
            'position' => 'Backend Developer',
            'grade' => 'middle',
            'status' => 'draft',
            'requirements' => [],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('requests', [
            'position' => 'Backend Developer',
            'status' => 'draft',
            'created_by' => $manager->id,
        ]);
    }

    public function test_supervisor_cannot_create_request(): void
    {
        $this->actingAs($this->supervisor())->post(route('requests.store'), [
            'position' => 'Backend Developer',
            'grade' => 'middle',
            'status' => 'draft',
        ])->assertForbidden();

        $this->assertDatabaseMissing('requests', ['position' => 'Backend Developer']);
    }

    public function test_creating_open_request_requires_at_least_one_must_requirement(): void
    {
        $manager = $this->manager();
        $tech = Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        // No requirements at all -> fails.
        $this->actingAs($manager)->post(route('requests.store'), [
            'position' => 'Backend Developer',
            'grade' => 'middle',
            'status' => 'open',
            'requirements' => [],
        ])->assertSessionHasErrors(['requirements']);

        // Only "nice" requirement -> still fails.
        $this->actingAs($manager)->post(route('requests.store'), [
            'position' => 'Backend Developer',
            'grade' => 'middle',
            'status' => 'open',
            'requirements' => [
                ['technology_id' => $tech->id, 'type' => 'nice', 'weight' => 2],
            ],
        ])->assertSessionHasErrors(['requirements']);

        // With a "must" requirement -> succeeds.
        $this->actingAs($manager)->post(route('requests.store'), [
            'position' => 'Backend Developer',
            'grade' => 'middle',
            'status' => 'open',
            'requirements' => [
                ['technology_id' => $tech->id, 'type' => 'must', 'weight' => 5],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('requests', ['position' => 'Backend Developer', 'status' => 'open']);
    }

    public function test_create_request_rejects_duplicate_technology_in_requirements(): void
    {
        $manager = $this->manager();
        $tech = Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        $this->actingAs($manager)->post(route('requests.store'), [
            'position' => 'Backend Developer',
            'grade' => 'middle',
            'status' => 'draft',
            'requirements' => [
                ['technology_id' => $tech->id, 'type' => 'must', 'weight' => 5],
                ['technology_id' => $tech->id, 'type' => 'nice', 'weight' => 2],
            ],
        ])->assertSessionHasErrors();
    }

    public function test_creator_can_edit_own_draft_request(): void
    {
        $manager = $this->manager();
        $tech = Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        $request = JobRequest::create([
            'position' => 'Backend Developer',
            'grade' => 'middle',
            'status' => 'draft',
            'created_by' => $manager->id,
        ]);

        $response = $this->actingAs($manager)->put(route('requests.update', $request), [
            'position' => 'Senior Backend Developer',
            'grade' => 'senior',
            'status' => 'draft',
            'requirements' => [
                ['technology_id' => $tech->id, 'type' => 'must', 'weight' => 5],
            ],
        ]);

        $response->assertRedirect();
        $this->assertSame('Senior Backend Developer', $request->fresh()->position);
        $this->assertCount(1, $request->fresh()->requirements);
    }

    public function test_other_manager_cannot_edit_someone_elses_request(): void
    {
        $owner = $this->manager();
        $otherManager = $this->manager();

        $request = JobRequest::create([
            'position' => 'Backend Developer',
            'grade' => 'middle',
            'status' => 'draft',
            'created_by' => $owner->id,
        ]);

        $this->actingAs($otherManager)->put(route('requests.update', $request), [
            'position' => 'Changed',
            'grade' => 'middle',
            'status' => 'draft',
        ])->assertForbidden();

        $this->assertSame('Backend Developer', $request->fresh()->position);
    }

    public function test_admin_can_edit_any_request(): void
    {
        $manager = $this->manager();
        $admin = $this->admin();

        $request = JobRequest::create([
            'position' => 'Backend Developer',
            'grade' => 'middle',
            'status' => 'draft',
            'created_by' => $manager->id,
        ]);

        $this->actingAs($admin)->put(route('requests.update', $request), [
            'position' => 'Changed by admin',
            'grade' => 'middle',
            'status' => 'draft',
        ])->assertRedirect();

        $this->assertSame('Changed by admin', $request->fresh()->position);
    }

    public function test_status_cannot_move_backward(): void
    {
        $manager = $this->manager();
        $tech = Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        $request = JobRequest::create([
            'position' => 'Backend Developer',
            'grade' => 'middle',
            'status' => 'open',
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)->put(route('requests.update', $request), [
            'position' => 'Backend Developer',
            'grade' => 'middle',
            'status' => 'draft', // attempting to go backward
            'requirements' => [
                ['technology_id' => $tech->id, 'type' => 'must', 'weight' => 5],
            ],
        ])->assertSessionHasErrors(['status']);

        $this->assertSame('open', $request->fresh()->status);
    }

    public function test_closed_request_cannot_be_edited(): void
    {
        $manager = $this->manager();

        $request = JobRequest::create([
            'position' => 'Backend Developer',
            'grade' => 'middle',
            'status' => 'closed',
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)->put(route('requests.update', $request), [
            'position' => 'Changed',
            'grade' => 'middle',
            'status' => 'closed',
        ])->assertForbidden();

        $this->assertSame('Backend Developer', $request->fresh()->position);
    }

    public function test_closed_request_cannot_be_edited_even_by_admin(): void
    {
        $manager = $this->manager();
        $admin = $this->admin();

        $request = JobRequest::create([
            'position' => 'Backend Developer',
            'grade' => 'middle',
            'status' => 'closed',
            'created_by' => $manager->id,
        ]);

        $this->actingAs($admin)->put(route('requests.update', $request), [
            'position' => 'Changed',
            'grade' => 'middle',
            'status' => 'closed',
        ])->assertForbidden();
    }

    public function test_filters_by_status_and_grade(): void
    {
        $manager = $this->manager();

        JobRequest::create(['position' => 'A', 'grade' => 'junior', 'status' => 'draft', 'created_by' => $manager->id]);
        JobRequest::create(['position' => 'B', 'grade' => 'senior', 'status' => 'open', 'created_by' => $manager->id]);

        $response = $this->actingAs($manager)->get(route('requests.index', ['status' => 'open']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('requests.data.0.position', 'B')
            ->where('requests.data', fn ($data) => count($data) === 1)
        );
    }

    public function test_creator_can_delete_own_request(): void
    {
        $manager = $this->manager();

        $request = JobRequest::create([
            'position' => 'Backend Developer',
            'grade' => 'middle',
            'status' => 'draft',
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->delete(route('requests.destroy', $request))
            ->assertRedirect(route('requests.index'));

        $this->assertDatabaseMissing('requests', ['id' => $request->id]);
    }
}