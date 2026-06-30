<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    use HasFactory;

    public const GRADES = ['junior', 'middle', 'senior', 'lead'];

    protected $fillable = [
        'full_name',
        'file_path',
        'raw_text',
        'grade',
        'location',
        'uploaded_by',
    ];

    /**
     * The user who uploaded this candidate's resume.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Skills (technologies) detected for this candidate.
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'candidate_skill');
    }

    /**
     * Assessments (match results) performed for this candidate.
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }
}