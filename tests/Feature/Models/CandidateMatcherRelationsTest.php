<?php

namespace Tests\Feature\Models;

use App\Models\Assessment;
use App\Models\AssessmentRequirement;
use App\Models\Candidate;
use App\Models\JobRequest;
use App\Models\Requirement;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateMatcherRelationsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeRequest(User $user): JobRequest
    {
        return JobRequest::create([
            'position' => 'Backend Developer',
            'description' => 'PHP/Laravel position',
            'grade' => 'middle',
            'location' => 'Vilnius',
            'citizenship' => 'EU',
            'needed_by' => now()->addMonth(),
            'status' => 'open',
            'created_by' => $user->id,
        ]);
    }

    public function test_technology_can_be_created_with_synonyms(): void
    {
        $tech = Technology::create([
            'name' => 'JavaScript',
            'group' => 'Frontend',
            'synonyms' => ['JS', 'ECMAScript'],
        ]);

        $this->assertDatabaseHas('technologies', ['name' => 'JavaScript']);
        $this->assertSame(['JS', 'ECMAScript'], $tech->fresh()->synonyms);
    }

    public function test_request_belongs_to_creator(): void
    {
        $user = User::factory()->create();
        $request = $this->makeRequest($user);

        $this->assertTrue($request->creator->is($user));
        $this->assertTrue($user->createdRequests->contains($request));
    }

    public function test_requirement_links_request_and_technology(): void
    {
        $user = User::factory()->create();
        $request = $this->makeRequest($user);
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        $requirement = Requirement::create([
            'request_id' => $request->id,
            'technology_id' => $php->id,
            'type' => 'must',
            'weight' => 5,
        ]);

        $this->assertTrue($requirement->request->is($request));
        $this->assertTrue($requirement->technology->is($php));
        $this->assertTrue($request->requirements->contains($requirement));
        $this->assertTrue($requirement->isMust());
    }

    public function test_requirement_requires_unique_technology_per_request(): void
    {
        $user = User::factory()->create();
        $request = $this->makeRequest($user);
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        Requirement::create([
            'request_id' => $request->id,
            'technology_id' => $php->id,
            'type' => 'must',
            'weight' => 5,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Requirement::create([
            'request_id' => $request->id,
            'technology_id' => $php->id,
            'type' => 'nice',
            'weight' => 1,
        ]);
    }

    public function test_candidate_can_have_multiple_skills(): void
    {
        $user = User::factory()->create();
        $candidate = Candidate::create([
            'full_name' => 'John Doe',
            'file_path' => 'resumes/john.pdf',
            'raw_text' => 'Experienced PHP and React developer',
            'grade' => 'senior',
            'location' => 'Vilnius',
            'uploaded_by' => $user->id,
        ]);

        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);
        $react = Technology::create(['name' => 'React', 'group' => 'Frontend']);

        $candidate->skills()->attach([$php->id, $react->id]);

        $this->assertCount(2, $candidate->fresh()->skills);
        $this->assertTrue($candidate->uploader->is($user));
        $this->assertTrue($php->candidates->contains($candidate));
    }

    public function test_assessment_links_request_and_candidate_with_requirement_results(): void
    {
        $user = User::factory()->create();
        $request = $this->makeRequest($user);
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);
        $docker = Technology::create(['name' => 'Docker', 'group' => 'DevOps']);

        $mustReq = Requirement::create([
            'request_id' => $request->id,
            'technology_id' => $php->id,
            'type' => 'must',
            'weight' => 5,
        ]);

        $niceReq = Requirement::create([
            'request_id' => $request->id,
            'technology_id' => $docker->id,
            'type' => 'nice',
            'weight' => 2,
        ]);

        $candidate = Candidate::create([
            'full_name' => 'Jane Doe',
            'file_path' => 'resumes/jane.pdf',
            'grade' => 'middle',
            'uploaded_by' => $user->id,
        ]);
        $candidate->skills()->attach($php->id);

        $assessment = Assessment::create([
            'request_id' => $request->id,
            'candidate_id' => $candidate->id,
            'coverage_percent' => 71,
            'grade_match' => true,
            'location_match' => false,
            'citizenship_match' => true,
            'calculated_by' => $user->id,
        ]);

        AssessmentRequirement::create([
            'assessment_id' => $assessment->id,
            'requirement_id' => $mustReq->id,
            'is_matched' => true,
        ]);

        AssessmentRequirement::create([
            'assessment_id' => $assessment->id,
            'requirement_id' => $niceReq->id,
            'is_matched' => false,
        ]);

        $this->assertTrue($assessment->request->is($request));
        $this->assertTrue($assessment->candidate->is($candidate));
        $this->assertCount(1, $assessment->matchedRequirements);
        $this->assertCount(1, $assessment->missingRequirements);
        $this->assertSame(71, $assessment->coverage_percent);
    }

    public function test_assessment_requires_unique_request_candidate_pair(): void
    {
        $user = User::factory()->create();
        $request = $this->makeRequest($user);
        $candidate = Candidate::create([
            'full_name' => 'Jane Doe',
            'file_path' => 'resumes/jane.pdf',
            'uploaded_by' => $user->id,
        ]);

        Assessment::create([
            'request_id' => $request->id,
            'candidate_id' => $candidate->id,
            'coverage_percent' => 50,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Assessment::create([
            'request_id' => $request->id,
            'candidate_id' => $candidate->id,
            'coverage_percent' => 80,
        ]);
    }

    public function test_deleting_request_cascades_to_requirements_and_assessments(): void
    {
        $user = User::factory()->create();
        $request = $this->makeRequest($user);
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        $requirement = Requirement::create([
            'request_id' => $request->id,
            'technology_id' => $php->id,
            'type' => 'must',
            'weight' => 5,
        ]);

        $candidate = Candidate::create([
            'full_name' => 'Jane Doe',
            'file_path' => 'resumes/jane.pdf',
            'uploaded_by' => $user->id,
        ]);

        $assessment = Assessment::create([
            'request_id' => $request->id,
            'candidate_id' => $candidate->id,
            'coverage_percent' => 50,
        ]);

        AssessmentRequirement::create([
            'assessment_id' => $assessment->id,
            'requirement_id' => $requirement->id,
            'is_matched' => true,
        ]);

        $request->delete();

        $this->assertDatabaseMissing('requirements', ['id' => $requirement->id]);
        $this->assertDatabaseMissing('assessments', ['id' => $assessment->id]);

        // Candidate itself should NOT be deleted when the request is removed.
        $this->assertDatabaseHas('candidates', ['id' => $candidate->id]);
    }

    public function test_deleting_candidate_cascades_to_skills_and_assessments(): void
    {
        $user = User::factory()->create();
        $request = $this->makeRequest($user);
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        $candidate = Candidate::create([
            'full_name' => 'Jane Doe',
            'file_path' => 'resumes/jane.pdf',
            'uploaded_by' => $user->id,
        ]);
        $candidate->skills()->attach($php->id);

        $assessment = Assessment::create([
            'request_id' => $request->id,
            'candidate_id' => $candidate->id,
            'coverage_percent' => 50,
        ]);

        $candidate->delete();

        $this->assertDatabaseMissing('candidate_skill', ['candidate_id' => $candidate->id]);
        $this->assertDatabaseMissing('assessments', ['id' => $assessment->id]);

        // Technology itself should NOT be deleted when a candidate is removed.
        $this->assertDatabaseHas('technologies', ['id' => $php->id]);
    }
}