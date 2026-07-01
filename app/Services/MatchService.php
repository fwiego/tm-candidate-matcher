<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentRequirement;
use App\Models\Candidate;
use App\Models\JobRequest;
use Illuminate\Support\Facades\DB;

class MatchService
{
    /**
     * Run a match between a candidate and a job request.
     *
     * Calculates a weighted coverage percentage across all requirements
     * (must and nice-to-have), records which requirements are matched,
     * and upserts the Assessment record so repeated runs overwrite the
     * previous result instead of accumulating history.
     */
    public function match(Candidate $candidate, JobRequest $jobRequest): Assessment
    {
        $jobRequest->loadMissing('requirements');
        $candidate->loadMissing('skills');

        $candidateSkillIds = $candidate->skills->pluck('id')->flip(); // flip for O(1) lookup

        $totalWeight = 0;
        $matchedWeight = 0;
        $requirementResults = []; // [requirement_id => is_matched]

        foreach ($jobRequest->requirements as $requirement) {
            $isMatched = $candidateSkillIds->has($requirement->technology_id);
            $totalWeight += $requirement->weight;

            if ($isMatched) {
                $matchedWeight += $requirement->weight;
            }

            $requirementResults[$requirement->id] = $isMatched;
        }

        $coveragePercent = $totalWeight > 0
            ? round(($matchedWeight / $totalWeight) * 100, 1)
            : 0.0;

        return DB::transaction(function () use ($candidate, $jobRequest, $coveragePercent, $requirementResults) {
            // Upsert the assessment — repeated runs overwrite the previous result.
            $assessment = Assessment::updateOrCreate(
                [
                    'candidate_id' => $candidate->id,
                    'request_id'   => $jobRequest->id,
                ],
                [
                    'coverage_percent' => $coveragePercent,
                    'calculated_by'    => auth()->id(),
                ]
            );

            // Replace per-requirement breakdown (delete old rows, insert fresh ones).
            $assessment->requirementResults()->delete();

            $rows = collect($requirementResults)
                ->map(fn ($isMatched, $reqId) => [
                    'assessment_id'  => $assessment->id,
                    'requirement_id' => $reqId,
                    'is_matched'     => $isMatched,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ])
                ->values()
                ->all();

            if (!empty($rows)) {
                AssessmentRequirement::insert($rows);
            }

            return $assessment->load(['requirementResults.requirement.technology']);
        });
    }
}