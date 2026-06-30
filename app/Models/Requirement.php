<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requirement extends Model
{
    use HasFactory;

    public const TYPE_MUST = 'must';

    public const TYPE_NICE = 'nice';

    protected $fillable = [
        'request_id',
        'technology_id',
        'type',
        'weight',
    ];

    /**
     * The request (vacancy) this requirement belongs to.
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(JobRequest::class, 'request_id');
    }

    /**
     * The technology this requirement is about.
     */
    public function technology(): BelongsTo
    {
        return $this->belongsTo(Technology::class);
    }

    /**
     * Assessment results referencing this requirement.
     */
    public function assessmentResults(): HasMany
    {
        return $this->hasMany(AssessmentRequirement::class);
    }

    public function isMust(): bool
    {
        return $this->type === self::TYPE_MUST;
    }
}