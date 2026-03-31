<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubmissionMember extends Model
{
    /** @use HasFactory<\Database\Factories\SubmissionMemberFactory> */
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

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}
