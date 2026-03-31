<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Submission extends Model
{
    /** @use HasFactory<\Database\Factories\SubmissionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id', 'submitted_by_email', 'submitted_by_name',
        'participation_type', 'team_name', 'status',
        'review_comment', 'reviewed_by', 'reviewed_at', 'form_answers'
    ];

    protected $casts = [
        'form_answers' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function members() 
    {
        return $this->hasMany(SubmissionMember::class);
    }
}
