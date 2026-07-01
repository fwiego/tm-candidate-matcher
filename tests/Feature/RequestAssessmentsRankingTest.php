<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Candidate;
use App\Models\JobRequest;
use App\Models\Role;
use App\Models\Technology;
use App\Models\User;
use App\Services\MatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestAssessmentsRankingTest extends TestCase
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

    public function test_request_show_includes_assessments_sorted_by_coverage(): void
    {
        $manager = $this->manager();
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);
        $laravel = Technology::create(['name' => 'Laravel', 'group' => 'Backend']);
        $docker = Technology::create(['name' => 'Docker', 'group' => 'DevOps']);

        $jobRequest = JobRequest::create([
            'position'   => 'Backend Developer',
            'grade'      => 'middle',
            'status'     => 'open',
            'created_by' => $manager->id,
        ]);

        $jobRequest->requirements()->createMany([
            ['technology_id' => $php->id, 'type' => 'must', 'weight' => 5],
            ['technology_id' => $laravel->id, 'type' => 'must', 'weight' => 5],
            ['technology_id' => $docker->id, 'type' => 'nice', 'weight' => 2],
        ]);

        // Candidate A: has all 3 → 100%
        $candidateA = Candidate::create([
            'full_name'   => 'Anna Senior',
            'file_path'   => 'resumes/a.pdf',
            'uploaded_by' => $manager->id,
        ]);
        $candidateA->skills()->sync([$php->id, $laravel->id, $docker->id]);

        // Candidate B: has only PHP → ~41.7%
        $candidateB = Candidate::create([
            'full_name'   => 'Bob Junior',
            'file_path'   => 'resumes/b.pdf',
            'uploaded_by' => $manager->id,
        ]);
        $candidateB->skills()->sync([$php->id]);

        // Candidate C: PHP + Laravel → ~83.3%
        $candidateC = Candidate::create([
            'full_name'   => 'Carol Middle',
            'file_path'   => 'resumes/c.pdf',
            'uploaded_by' => $manager->id,
        ]);
        $candidateC->skills()->sync([$php->id, $laravel->id]);

        $this->actingAs($manager);
        $service = app(MatchService::class);
        $service->match($candidateA, $jobRequest);
        $service->match($candidateB, $jobRequest);
        $service->match($candidateC, $jobRequest);

        $response = $this->actingAs($manager)->get(route('requests.show', $jobRequest));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Requests/Show')
            ->has('assessments', 3)
            ->where('assessments.0.candidate.full_name', 'Anna Senior')
            ->where('assessments.0.coverage_percent', 100)
            ->where('assessments.1.candidate.full_name', 'Carol Middle')
            ->where('assessments.2.candidate.full_name', 'Bob Junior')
        );
    }

    public function test_request_show_has_empty_assessments_when_none_exist(): void
    {
        $manager = $this->manager();

        $jobRequest = JobRequest::create([
            'position'   => 'Backend Developer',
            'grade'      => 'middle',
            'status'     => 'open',
            'created_by' => $manager->id,
        ]);

        $response = $this->actingAs($manager)->get(route('requests.show', $jobRequest));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('assessments', 0)
        );
    }

    public function test_supervisor_can_see_assessments_ranking(): void
    {
        $manager = $this->manager();
        $supervisor = $this->supervisor();

        $jobRequest = JobRequest::create([
            'position'   => 'Backend Developer',
            'grade'      => 'middle',
            'status'     => 'open',
            'created_by' => $manager->id,
        ]);

        $this->actingAs($supervisor)
            ->get(route('requests.show', $jobRequest))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('assessments', 0)
            );
    }
}