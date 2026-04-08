<?php

namespace App\Models;

use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'event_id', 'submitted_by_email', 'submitted_by_name',
        'participation_type', 'team_name', 'status',
        'review_comment', 'reviewed_by', 'reviewed_at', 'form_answers',
    ];

    protected $casts = [
        'form_answers' => 'array',
        'reviewed_at' => 'datetime',
    ];

    // Evento al que pertenece la inscripcion.
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    // Usuario que realizo la revision final.
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Miembros vinculados cuando la participacion es por equipo.
    public function members(): HasMany
    {
        return $this->hasMany(SubmissionMember::class);
    }

    /**
     * @return array<int, string>
     */
    public static function occupyingStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
        ];
    }
}
