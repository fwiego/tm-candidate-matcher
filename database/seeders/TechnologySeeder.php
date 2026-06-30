<?php

namespace Database\Seeders;

use App\Models\Technology;
use Illuminate\Database\Seeder;

class TechnologySeeder extends Seeder
{
    /**
     * Seed a starter set of technologies grouped by category, with common synonyms.
     */
    public function run(): void
    {
        $technologies = [
            ['name' => 'PHP', 'group' => 'Backend', 'synonyms' => []],
            ['name' => 'Laravel', 'group' => 'Backend', 'synonyms' => []],
            ['name' => 'Symfony', 'group' => 'Backend', 'synonyms' => []],
            ['name' => 'Python', 'group' => 'Backend', 'synonyms' => []],
            ['name' => 'Django', 'group' => 'Backend', 'synonyms' => []],
            ['name' => 'Java', 'group' => 'Backend', 'synonyms' => []],
            ['name' => 'Spring', 'group' => 'Backend', 'synonyms' => ['Spring Boot']],
            ['name' => 'Node.js', 'group' => 'Backend', 'synonyms' => ['NodeJS', 'Node']],
            ['name' => 'C#', 'group' => 'Backend', 'synonyms' => ['CSharp']],
            ['name' => '.NET', 'group' => 'Backend', 'synonyms' => ['DotNet', 'ASP.NET']],
            ['name' => 'Go', 'group' => 'Backend', 'synonyms' => ['Golang']],

            ['name' => 'JavaScript', 'group' => 'Frontend', 'synonyms' => ['JS', 'ECMAScript']],
            ['name' => 'TypeScript', 'group' => 'Frontend', 'synonyms' => ['TS']],
            ['name' => 'React', 'group' => 'Frontend', 'synonyms' => ['ReactJS']],
            ['name' => 'Vue', 'group' => 'Frontend', 'synonyms' => ['VueJS']],
            ['name' => 'Angular', 'group' => 'Frontend', 'synonyms' => []],
            ['name' => 'HTML', 'group' => 'Frontend', 'synonyms' => ['HTML5']],
            ['name' => 'CSS', 'group' => 'Frontend', 'synonyms' => ['CSS3']],
            ['name' => 'Tailwind CSS', 'group' => 'Frontend', 'synonyms' => ['Tailwind']],

            ['name' => 'PostgreSQL', 'group' => 'Database', 'synonyms' => ['Postgres']],
            ['name' => 'MySQL', 'group' => 'Database', 'synonyms' => []],
            ['name' => 'MongoDB', 'group' => 'Database', 'synonyms' => ['Mongo']],
            ['name' => 'Redis', 'group' => 'Database', 'synonyms' => []],

            ['name' => 'Docker', 'group' => 'DevOps', 'synonyms' => []],
            ['name' => 'Kubernetes', 'group' => 'DevOps', 'synonyms' => ['K8s']],
            ['name' => 'Git', 'group' => 'DevOps', 'synonyms' => []],
            ['name' => 'CI/CD', 'group' => 'DevOps', 'synonyms' => ['CICD']],
            ['name' => 'AWS', 'group' => 'DevOps', 'synonyms' => ['Amazon Web Services']],

            ['name' => 'REST API', 'group' => 'Other', 'synonyms' => ['RESTful API', 'REST']],
            ['name' => 'GraphQL', 'group' => 'Other', 'synonyms' => []],
        ];

        foreach ($technologies as $tech) {
            Technology::updateOrCreate(['name' => $tech['name']], $tech);
        }
    }
}