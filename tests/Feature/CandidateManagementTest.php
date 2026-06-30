<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Role;
use App\Models\Technology;
use App\Models\User;
use App\Services\ResumeParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CandidateManagementTest extends TestCase
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

        Storage::fake('local');
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

    /**
     * Mock the resume parser to return fixed text, avoiding a dependency on
     * the real `pdftotext` binary / PhpWord parsing for unit-level tests.
     */
    protected function mockParser(string $returnText): void
    {
        $this->mock(ResumeParserService::class, function ($mock) use ($returnText) {
            $mock->shouldReceive('extractText')->andReturn($returnText);
        });
    }

    public function test_manager_can_view_candidates_list(): void
    {
        $this->actingAs($this->manager())
            ->get(route('candidates.index'))
            ->assertOk();
    }

    public function test_supervisor_can_view_candidates_list(): void
    {
        $this->actingAs($this->supervisor())
            ->get(route('candidates.index'))
            ->assertOk();
    }

    public function test_guest_is_redirected_from_candidates_list(): void
    {
        $this->get(route('candidates.index'))
            ->assertRedirect(route('login'));
    }

    public function test_manager_can_upload_resume_and_skills_are_detected(): void
    {
        $manager = $this->manager();
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);
        $laravel = Technology::create(['name' => 'Laravel', 'group' => 'Backend']);
        Technology::create(['name' => 'Python', 'group' => 'Backend']);

        $this->mockParser('Experienced PHP and Laravel developer.');

        $file = UploadedFile::fake()->create('john_doe.pdf', 100, 'application/pdf');

        $response = $this->actingAs($manager)->post(route('candidates.store'), [
            'resume' => $file,
            'grade' => 'middle',
            'location' => 'Vilnius',
        ]);

        $response->assertRedirect();

        $candidate = Candidate::where('full_name', 'John Doe')->first();
        $this->assertNotNull($candidate);
        $this->assertSame('middle', $candidate->grade);
        $this->assertSame('Vilnius', $candidate->location);
        $this->assertCount(2, $candidate->skills);
        $this->assertTrue($candidate->skills->contains('id', $php->id));
        $this->assertTrue($candidate->skills->contains('id', $laravel->id));

        Storage::disk('local')->assertExists($candidate->file_path);
    }

    public function test_supervisor_cannot_upload_resume(): void
    {
        $this->mockParser('PHP developer.');

        $file = UploadedFile::fake()->create('jane.pdf', 100, 'application/pdf');

        $this->actingAs($this->supervisor())->post(route('candidates.store'), [
            'resume' => $file,
        ])->assertForbidden();

        $this->assertDatabaseMissing('candidates', ['full_name' => 'Jane']);
    }

    public function test_uploading_resume_with_existing_candidate_name_updates_instead_of_duplicating(): void
    {
        $manager = $this->manager();
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);
        $react = Technology::create(['name' => 'React', 'group' => 'Frontend']);

        // Mock parser to return different text on consecutive calls.
        $this->mock(ResumeParserService::class, function ($mock) {
            $mock->shouldReceive('extractText')
                ->once()
                ->andReturn('PHP developer.');

            $mock->shouldReceive('extractText')
                ->once()
                ->andReturn('React frontend developer.');
        });

        $this->actingAs($manager)->post(route('candidates.store'), [
            'resume' => UploadedFile::fake()->create('jane_doe.pdf', 100, 'application/pdf'),
        ]);

        $this->assertSame(1, Candidate::where('full_name', 'Jane Doe')->count());
        $firstCandidate = Candidate::where('full_name', 'Jane Doe')->first();
        $this->assertTrue($firstCandidate->skills->contains('id', $php->id));

        // Re-upload with the same guessed name but different content -> should UPDATE, not duplicate.
        $this->actingAs($manager)->post(route('candidates.store'), [
            'resume' => UploadedFile::fake()->create('jane_doe.pdf', 100, 'application/pdf'),
        ]);

        $this->assertSame(1, Candidate::where('full_name', 'Jane Doe')->count());

        $updatedCandidate = Candidate::where('full_name', 'Jane Doe')->first();
        $this->assertSame($firstCandidate->id, $updatedCandidate->id);
        $this->assertCount(1, $updatedCandidate->skills);
        $this->assertTrue($updatedCandidate->skills->contains('id', $react->id));
        $this->assertFalse($updatedCandidate->skills->contains('id', $php->id));
    }

    public function test_candidate_name_is_guessed_from_filename(): void
    {
        $manager = $this->manager();
        $this->mockParser('Some text.');

        $this->actingAs($manager)->post(route('candidates.store'), [
            'resume' => UploadedFile::fake()->create('ivan_petrov_cv.pdf', 100, 'application/pdf'),
        ]);

        $this->assertDatabaseHas('candidates', ['full_name' => 'Ivan Petrov Cv']);
    }

    public function test_upload_requires_pdf_or_docx_file(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->post(route('candidates.store'), [
            'resume' => UploadedFile::fake()->create('resume.txt', 100, 'text/plain'),
        ])->assertSessionHasErrors(['resume']);
    }

    public function test_admin_can_update_candidate_manually(): void
    {
        $admin = $this->admin();
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);
        $react = Technology::create(['name' => 'React', 'group' => 'Frontend']);

        $candidate = Candidate::create([
            'full_name' => 'Test Candidate',
            'file_path' => 'resumes/test.pdf',
            'uploaded_by' => $admin->id,
        ]);
        $candidate->skills()->attach($php->id);

        $response = $this->actingAs($admin)->put(route('candidates.update', $candidate), [
            'full_name' => 'Updated Name',
            'grade' => 'senior',
            'location' => 'Kaunas',
            'skills' => [$react->id],
        ]);

        $response->assertRedirect();

        $candidate->refresh();
        $this->assertSame('Updated Name', $candidate->full_name);
        $this->assertSame('senior', $candidate->grade);
        $this->assertCount(1, $candidate->skills);
        $this->assertTrue($candidate->skills->contains('id', $react->id));
        $this->assertFalse($candidate->skills->contains('id', $php->id));
    }

    public function test_filters_candidates_by_technology(): void
    {
        $manager = $this->manager();
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);
        $python = Technology::create(['name' => 'Python', 'group' => 'Backend']);

        $phpCandidate = Candidate::create([
            'full_name' => 'PHP Dev',
            'file_path' => 'resumes/php.pdf',
            'uploaded_by' => $manager->id,
        ]);
        $phpCandidate->skills()->attach($php->id);

        $pythonCandidate = Candidate::create([
            'full_name' => 'Python Dev',
            'file_path' => 'resumes/python.pdf',
            'uploaded_by' => $manager->id,
        ]);
        $pythonCandidate->skills()->attach($python->id);

        $response = $this->actingAs($manager)->get(route('candidates.index', ['technology_id' => $php->id]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('candidates.data.0.full_name', 'PHP Dev')
            ->where('candidates.data', fn ($data) => count($data) === 1)
        );
    }

    public function test_creator_can_delete_candidate(): void
    {
        $manager = $this->manager();

        $candidate = Candidate::create([
            'full_name' => 'To Delete',
            'file_path' => 'resumes/delete.pdf',
            'uploaded_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->delete(route('candidates.destroy', $candidate))
            ->assertRedirect(route('candidates.index'));

        $this->assertDatabaseMissing('candidates', ['id' => $candidate->id]);
    }
}