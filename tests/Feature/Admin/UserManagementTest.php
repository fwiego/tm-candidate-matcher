<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;

    protected Role $managerRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::create(['slug' => 'admin', 'name' => 'Админ']);
        $this->managerRole = Role::create(['slug' => 'manager', 'name' => 'Менеджер']);
        Role::create(['slug' => 'supervisor', 'name' => 'Руководитель']);
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

    public function test_admin_can_view_users_list(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_non_admin_cannot_view_users_list(): void
    {
        $this->actingAs($this->manager())
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_users_list(): void
    {
        $this->get(route('admin.users.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_create_user(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'roles' => [$this->managerRole->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
        ]);

        $created = User::where('email', 'newuser@example.com')->first();
        $this->assertTrue($created->hasRole('manager'));
    }

    public function test_non_admin_cannot_create_user(): void
    {
        $this->actingAs($this->manager())->post(route('admin.users.store'), [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'Password123!',
            'roles' => [$this->managerRole->id],
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'email' => 'newuser@example.com',
        ]);
    }

    public function test_create_user_requires_valid_data(): void
    {
        $this->actingAs($this->admin())->post(route('admin.users.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'password' => '',
            'roles' => [],
        ])->assertSessionHasErrors(['name', 'email', 'password', 'roles']);
    }

    public function test_admin_can_update_user(): void
    {
        $target = $this->manager();

        $response = $this->actingAs($this->admin())->put(route('admin.users.update', $target), [
            'name' => 'Updated Name',
            'email' => $target->email,
            'password' => '',
            'roles' => [$this->adminRole->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $target->refresh();
        $this->assertSame('Updated Name', $target->name);
        $this->assertTrue($target->hasRole('admin'));
        $this->assertFalse($target->hasRole('manager'));
    }

    public function test_admin_can_delete_other_user(): void
    {
        $target = $this->manager();

        $this->actingAs($this->admin())
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}