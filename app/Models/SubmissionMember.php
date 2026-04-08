<?php

namespace App\Models;

use Database\Factories\SubmissionMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubmissionMember extends Model
{
    /** @use HasFactory<SubmissionMemberFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'submission_id',
        'full_name',
        'email',
        'is_captain',
    ];

    protected $casts = [
        'is_captain' => 'boolean',
    ];

    // Submission padre del miembro.
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
