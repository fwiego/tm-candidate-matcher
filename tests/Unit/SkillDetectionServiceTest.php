<?php

namespace Tests\Unit;

use App\Models\Technology;
use App\Services\SkillDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SkillDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SkillDetectionService;
    }

    public function test_detects_exact_technology_name(): void
    {
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        $result = $this->service->detect('Experienced PHP developer with 5 years.');

        $this->assertTrue($result->contains('id', $php->id));
    }

    public function test_detects_technology_via_synonym(): void
    {
        $js = Technology::create([
            'name' => 'JavaScript',
            'group' => 'Frontend',
            'synonyms' => ['JS', 'ECMAScript'],
        ]);

        $result = $this->service->detect('Strong knowledge of JS and modern frameworks.');

        $this->assertTrue($result->contains('id', $js->id));
    }

    public function test_is_case_insensitive(): void
    {
        $docker = Technology::create(['name' => 'Docker', 'group' => 'DevOps']);

        $result = $this->service->detect('Used docker and DOCKER-compose extensively.');

        $this->assertTrue($result->contains('id', $docker->id));
    }

    public function test_does_not_match_substring_inside_another_word(): void
    {
        $java = Technology::create(['name' => 'Java', 'group' => 'Backend']);

        // "JavaScript" contains "Java" as a substring but should NOT match "Java".
        $result = $this->service->detect('Frontend developer skilled in JavaScript.');

        $this->assertFalse($result->contains('id', $java->id));
    }

    public function test_matches_technologies_with_symbols(): void
    {
        $csharp = Technology::create(['name' => 'C#', 'group' => 'Backend']);
        $dotnet = Technology::create(['name' => '.NET', 'group' => 'Backend', 'synonyms' => ['DotNet']]);

        $result = $this->service->detect('Senior C# developer with .NET experience.');

        $this->assertTrue($result->contains('id', $csharp->id));
        $this->assertTrue($result->contains('id', $dotnet->id));
    }

    public function test_returns_empty_collection_for_empty_text(): void
    {
        Technology::create(['name' => 'PHP', 'group' => 'Backend']);

        $result = $this->service->detect('');

        $this->assertTrue($result->isEmpty());
    }

    public function test_returns_empty_collection_when_nothing_matches(): void
    {
        Technology::create(['name' => 'PHP', 'group' => 'Backend']);
        Technology::create(['name' => 'Laravel', 'group' => 'Backend']);

        $result = $this->service->detect('Experienced chef and barista.');

        $this->assertTrue($result->isEmpty());
    }

    public function test_detects_multiple_technologies(): void
    {
        $php = Technology::create(['name' => 'PHP', 'group' => 'Backend']);
        $laravel = Technology::create(['name' => 'Laravel', 'group' => 'Backend']);
        $docker = Technology::create(['name' => 'Docker', 'group' => 'DevOps']);
        Technology::create(['name' => 'Python', 'group' => 'Backend']);

        $result = $this->service->detect('PHP and Laravel developer, deploys with Docker.');

        $this->assertCount(3, $result);
        $this->assertTrue($result->contains('id', $php->id));
        $this->assertTrue($result->contains('id', $laravel->id));
        $this->assertTrue($result->contains('id', $docker->id));
    }
}