<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobRequest extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'requests';

    public const GRADES = ['junior', 'middle', 'senior', 'lead'];

    public const STATUSES = ['draft', 'open', 'closed'];

    protected $fillable = [
        'position',
        'description',
        'grade',
        'location',
        'citizenship',
        'needed_by',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'needed_by' => 'date',
        ];
    }

    /**
     * The user who created this request.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Requirements attached to this request.
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(Requirement::class, 'request_id');
    }

    /**
     * Assessments (match results) performed against this request.
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'request_id');
    }
}