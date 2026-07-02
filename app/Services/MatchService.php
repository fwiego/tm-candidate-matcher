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
     * Grade order for comparison: lower index = lower grade.
     */
    private const GRADE_ORDER = ['junior', 'middle', 'senior', 'lead'];

    /**
     * Run a match between a candidate and a job request.
     *
     * - Coverage percent is calculated from weighted skill requirements.
     * - If the candidate's grade is lower than the request's grade,
     *   the coverage is penalised proportionally to how many levels below they are.
     * - Location and citizenship are stored as boolean flags and displayed
     *   on the UI but do NOT affect the coverage percent.
     */
    public function match(Candidate $candidate, JobRequest $jobRequest): Assessment
    {
        $jobRequest->loadMissing('requirements');
        $candidate->loadMissing('skills');

        $candidateSkillIds = $candidate->skills->pluck('id')->flip();

        $totalWeight   = 0;
        $matchedWeight = 0;
        $requirementResults = [];

        foreach ($jobRequest->requirements as $requirement) {
            $isMatched = $candidateSkillIds->has($requirement->technology_id);
            $totalWeight += $requirement->weight;

            if ($isMatched) {
                $matchedWeight += $requirement->weight;
            }

            $requirementResults[$requirement->id] = $isMatched;
        }

        // Base coverage from skills.
        $baseCoverage = $totalWeight > 0
            ? ($matchedWeight / $totalWeight) * 100
            : 0.0;

        // Grade penalty: each level below requirement reduces coverage by 15%.
        $gradePenalty = $this->calculateGradePenalty(
            $candidate->grade,
            $jobRequest->grade
        );

        $coveragePercent = round(max(0, $baseCoverage * (1 - $gradePenalty)), 1);

        // Location and citizenship flags (informational only).
        $locationMatch = $this->matchesText(
            $candidate->location,
            $jobRequest->location
        );

        $citizenshipMatch = $this->matchesText(
            $candidate->location,
            $jobRequest->citizenship
        );

        return DB::transaction(function () use (
            $candidate, $jobRequest, $coveragePercent,
            $requirementResults, $locationMatch, $citizenshipMatch
        ) {
            $assessment = Assessment::updateOrCreate(
                [
                    'candidate_id' => $candidate->id,
                    'request_id'   => $jobRequest->id,
                ],
                [
                    'coverage_percent'  => $coveragePercent,
                    'grade_match'       => $this->gradeMatches($candidate->grade, $jobRequest->grade),
                    'location_match'    => $locationMatch,
                    'citizenship_match' => $citizenshipMatch,
                    'calculated_by'     => auth()->id(),
                ]
            );

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

    /**
     * Calculate a penalty factor (0.0–1.0) based on how many grade levels
     * the candidate is below the job request requirement.
     * Each level below = 15% penalty. Being at or above = 0% penalty.
     */
    protected function calculateGradePenalty(?string $candidateGrade, ?string $requestGrade): float
    {
        if (!$candidateGrade || !$requestGrade) {
            return 0.0;
        }

        $candidateIndex = array_search($candidateGrade, self::GRADE_ORDER);
        $requestIndex   = array_search($requestGrade, self::GRADE_ORDER);

        if ($candidateIndex === false || $requestIndex === false) {
            return 0.0;
        }

        $levelsBellow = $requestIndex - $candidateIndex;

        if ($levelsBellow <= 0) {
            return 0.0;
        }

        return min(1.0, $levelsBellow * 0.15);
    }

    /**
     * Check whether candidate's grade is equal to or above the required grade.
     */
    protected function gradeMatches(?string $candidateGrade, ?string $requestGrade): bool
    {
        if (!$candidateGrade || !$requestGrade) {
            return false;
        }

        $candidateIndex = array_search($candidateGrade, self::GRADE_ORDER);
        $requestIndex   = array_search($requestGrade, self::GRADE_ORDER);

        if ($candidateIndex === false || $requestIndex === false) {
            return false;
        }

        return $candidateIndex >= $requestIndex;
    }

    /**
     * Simple case-insensitive substring match for location/citizenship.
     * Returns true if either value is empty (no requirement specified).
     */
    protected function matchesText(?string $candidateValue, ?string $requestValue): bool
    {
        if (!$requestValue) {
            return true;
        }

        if (!$candidateValue) {
            return false;
        }

        return str_contains(
            mb_strtolower($candidateValue),
            mb_strtolower($requestValue)
        ) || str_contains(
            mb_strtolower($requestValue),
            mb_strtolower($candidateValue)
        );
    }
}