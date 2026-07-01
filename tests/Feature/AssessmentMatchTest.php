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

class AssessmentMatchTest extends TestCase
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

    protected function makeCandidate(User $uploader, array $skillIds = []): Candidate
    {
        $candidate = Candidate::create([
            'full_name'   => 'Test Candidate',
            'file_path'   => 'resumes/test.pdf',
            'uploaded_by' => $uploader->id,
        ]);

        if ($skillIds) {
            $candidate->skills()->sync($skillIds);
        }

        return $candidate;
    }

    protected function makeRequest(User $creator, array $requirements = []): JobRequest
    {
        $request = JobRequest::create([
            'position'   => 'Backend Developer',
            'grade'      => 'middle',
            'status'     => 'open',
            'created_by' => $creator->id,
        ]);

        foreach ($requirements as $req) {
            $request->requirements()->create($req);
        }

        return $request;
    }

    public function test_full_coverage_when_candidate_has_all_skills(): void
    {
        $manager = $this->manager();
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);
        $laravel = Technology::create(['name' => 'Laravel', 'group' => 'Backend']);

        $candidate = $this->makeCandidate($manager, [$php->id, $laravel->id]);
        $jobRequest = $this->makeRequest($manager, [
            ['technology_id' => $php->id, 'type' => 'must', 'weight' => 5],
            ['technology_id' => $laravel->id, 'type' => 'must', 'weight' => 5],
        ]);

        $this->actingAs($manager);
        $assessment = app(MatchService::class)->match($candidate, $jobRequest);

        $this->assertEquals(100.0, $assessment->coverage_percent);
        $this->assertCount(2, $assessment->requirementResults);
        $this->assertTrue($assessment->requirementResults->every(fn ($r) => $r->is_matched));
    }

    public function test_zero_coverage_when_candidate_has_no_matching_skills(): void
    {
        $manager = $this->manager();
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);
        $python = Technology::create(['name' => 'Python', 'group' => 'Backend']);

        $candidate = $this->makeCandidate($manager, [$python->id]);
        $jobRequest = $this->makeRequest($manager, [
            ['technology_id' => $php->id, 'type' => 'must', 'weight' => 10],
        ]);

        $this->actingAs($manager);
        $assessment = app(MatchService::class)->match($candidate, $jobRequest);

        $this->assertEquals(0.0, $assessment->coverage_percent);
        $this->assertFalse($assessment->requirementResults->first()->is_matched);
    }

    public function test_coverage_is_weighted(): void
    {
        $manager = $this->manager();
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);
        $docker = Technology::create(['name' => 'Docker', 'group' => 'DevOps']);

        $candidate = $this->makeCandidate($manager, [$php->id]);
        $jobRequest = $this->makeRequest($manager, [
            ['technology_id' => $php->id, 'type' => 'must', 'weight' => 8],
            ['technology_id' => $docker->id, 'type' => 'nice', 'weight' => 2],
        ]);

        $this->actingAs($manager);
        $assessment = app(MatchService::class)->match($candidate, $jobRequest);

        $this->assertEquals(80.0, $assessment->coverage_percent);
    }

    public function test_repeated_match_overwrites_previous_result(): void
    {
        $manager = $this->manager();
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);
        $laravel = Technology::create(['name' => 'Laravel', 'group' => 'Backend']);

        $candidate = $this->makeCandidate($manager, [$php->id]);
        $jobRequest = $this->makeRequest($manager, [
            ['technology_id' => $php->id, 'type' => 'must', 'weight' => 5],
            ['technology_id' => $laravel->id, 'type' => 'must', 'weight' => 5],
        ]);

        $this->actingAs($manager);
        $service = app(MatchService::class);

        $first = $service->match($candidate, $jobRequest);
        $this->assertEquals(50.0, $first->coverage_percent);

        $candidate->skills()->sync([$php->id, $laravel->id]);
        $candidate->load('skills');

        $second = $service->match($candidate, $jobRequest);
        $this->assertEquals(100.0, $second->coverage_percent);

        $this->assertSame(1, Assessment::where([
            'candidate_id' => $candidate->id,
            'request_id'   => $jobRequest->id,
        ])->count());
    }

    public function test_zero_coverage_for_request_with_no_requirements(): void
    {
        $manager = $this->manager();
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        $candidate = $this->makeCandidate($manager, [$php->id]);
        $jobRequest = $this->makeRequest($manager, []);

        $this->actingAs($manager);
        $assessment = app(MatchService::class)->match($candidate, $jobRequest);

        $this->assertEquals(0.0, $assessment->coverage_percent);
        $this->assertCount(0, $assessment->requirementResults);
    }

    public function test_manager_can_access_create_assessment_page(): void
    {
        $this->actingAs($this->manager())
            ->get(route('assessments.create'))
            ->assertOk();
    }

    public function test_supervisor_cannot_run_assessment(): void
    {
        $manager = $this->manager();
        $supervisor = $this->supervisor();
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        $candidate = $this->makeCandidate($manager);
        $jobRequest = $this->makeRequest($manager, [
            ['technology_id' => $php->id, 'type' => 'must', 'weight' => 5],
        ]);

        $this->actingAs($supervisor)->post(route('assessments.store'), [
            'candidate_id' => $candidate->id,
            'request_id'   => $jobRequest->id,
        ])->assertForbidden();
    }

    public function test_manager_can_run_assessment_via_controller(): void
    {
        $manager = $this->manager();
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        $candidate = $this->makeCandidate($manager, [$php->id]);
        $jobRequest = $this->makeRequest($manager, [
            ['technology_id' => $php->id, 'type' => 'must', 'weight' => 5],
        ]);

        $response = $this->actingAs($manager)->post(route('assessments.store'), [
            'candidate_id' => $candidate->id,
            'request_id'   => $jobRequest->id,
        ]);

        $response->assertRedirect();

        $assessment = Assessment::where([
            'candidate_id' => $candidate->id,
            'request_id'   => $jobRequest->id,
        ])->first();

        $this->assertNotNull($assessment);
        $this->assertEquals(100.0, $assessment->coverage_percent);
    }

    public function test_assessment_show_page_accessible_by_supervisor(): void
    {
        $manager = $this->manager();
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        $candidate = $this->makeCandidate($manager, [$php->id]);
        $jobRequest = $this->makeRequest($manager, [
            ['technology_id' => $php->id, 'type' => 'must', 'weight' => 5],
        ]);

        $this->actingAs($manager);
        $assessment = app(MatchService::class)->match($candidate, $jobRequest);

        $this->actingAs($this->supervisor())
            ->get(route('assessments.show', $assessment))
            ->assertOk();
    }
}