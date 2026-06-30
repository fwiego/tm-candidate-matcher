<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'candidate_id',
        'coverage_percent',
        'grade_match',
        'location_match',
        'citizenship_match',
        'calculated_by',
    ];

    protected function casts(): array
    {
        return [
            'grade_match' => 'boolean',
            'location_match' => 'boolean',
            'citizenship_match' => 'boolean',
        ];
    }

    /**
     * The request (vacancy) this assessment was performed against.
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(JobRequest::class, 'request_id');
    }

    /**
     * The candidate this assessment was performed for.
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * The user who triggered this assessment calculation.
     */
    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    /**
     * Per-requirement match details for this assessment.
     */
    public function requirementResults(): HasMany
    {
        return $this->hasMany(AssessmentRequirement::class);
    }

    /**
     * Requirements that were matched by the candidate's skills.
     */
    public function matchedRequirements(): HasMany
    {
        return $this->requirementResults()->where('is_matched', true);
    }

    /**
     * Requirements that were NOT matched (missing skills).
     */
    public function missingRequirements(): HasMany
    {
        return $this->requirementResults()->where('is_matched', false);
    }
}