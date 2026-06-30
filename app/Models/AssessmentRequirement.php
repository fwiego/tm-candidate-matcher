<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentRequirement extends Model
{
    use HasFactory;

    protected $table = 'assessment_requirement';

    protected $fillable = [
        'assessment_id',
        'requirement_id',
        'is_matched',
    ];

    protected function casts(): array
    {
        return [
            'is_matched' => 'boolean',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class);
    }
}