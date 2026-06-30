<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Technology extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'group',
        'synonyms',
    ];

    protected function casts(): array
    {
        return [
            'synonyms' => 'array',
        ];
    }

    /**
     * Requirements referencing this technology.
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(Requirement::class);
    }

    /**
     * Candidates who have this skill.
     */
    public function candidates(): BelongsToMany
    {
        return $this->belongsToMany(Candidate::class, 'candidate_skill');
    }
}