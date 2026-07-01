<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\JobRequest;
use App\Models\Role;
use App\Models\Technology;
use App\Models\User;
use App\Services\MatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateAssessmentsHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected Role $managerRole;
    protected Role $supervisorRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->managerRole = Role::create(['slug' => 'manager', 'name' => 'Менеджер']);
        $this->supervisorRole = Role::create(['slug' => 'supervisor', 'name' => 'Руководитель']);
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

    public function test_candidate_show_includes_assessments_sorted_by_coverage(): void
    {
        $manager = $this->manager();
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);
        $laravel = Technology::create(['name' => 'Laravel', 'group' => 'Backend']);

        $candidate = Candidate::create([
            'full_name'   => 'Ivan Petrov',
            'file_path'   => 'resumes/ivan.pdf',
            'uploaded_by' => $manager->id,
        ]);
        $candidate->skills()->sync([$php->id, $laravel->id]);

        // Request A: requires PHP + Laravel → 100%
        $requestA = JobRequest::create([
            'position'   => 'Senior Backend',
            'grade'      => 'senior',
            'status'     => 'open',
            'created_by' => $manager->id,
        ]);
        $requestA->requirements()->createMany([
            ['technology_id' => $php->id, 'type' => 'must', 'weight' => 5],
            ['technology_id' => $laravel->id, 'type' => 'must', 'weight' => 5],
        ]);

        // Request B: requires PHP (must) + Laravel (nice) → 100% too, but created second
        $requestB = JobRequest::create([
            'position'   => 'Junior Backend',
            'grade'      => 'junior',
            'status'     => 'open',
            'created_by' => $manager->id,
        ]);
        $requestB->requirements()->createMany([
            ['technology_id' => $php->id, 'type' => 'must', 'weight' => 5],
            ['technology_id' => $laravel->id, 'type' => 'nice', 'weight' => 5],
        ]);

        $this->actingAs($manager);
        $service = app(MatchService::class);
        $service->match($candidate, $requestA);
        $service->match($candidate, $requestB);

        $response = $this->actingAs($manager)->get(route('candidates.show', $candidate));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Candidates/Show')
            ->has('assessments', 2)
            ->where('assessments.0.coverage_percent', 100)
            ->where('assessments.1.coverage_percent', 100)
        );
    }

    public function test_candidate_show_has_empty_assessments_when_none_exist(): void
    {
        $manager = $this->manager();

        $candidate = Candidate::create([
            'full_name'   => 'Ivan Petrov',
            'file_path'   => 'resumes/ivan.pdf',
            'uploaded_by' => $manager->id,
        ]);

        $response = $this->actingAs($manager)->get(route('candidates.show', $candidate));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('assessments', 0)
        );
    }

    public function test_supervisor_can_see_candidate_assessments_history(): void
    {
        $manager = $this->manager();
        $supervisor = $this->supervisor();

        $candidate = Candidate::create([
            'full_name'   => 'Ivan Petrov',
            'file_path'   => 'resumes/ivan.pdf',
            'uploaded_by' => $manager->id,
        ]);

        $this->actingAs($supervisor)
            ->get(route('candidates.show', $candidate))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('assessments', 0)
            );
    }
}