<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(TechnologySeeder::class);

        // User::factory(10)->create();

        $admin = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
        ]);

        $admin->roles()->attach(Role::where('slug', 'admin')->first());
    }
}