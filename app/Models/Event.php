<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'type',
        'modality',
        'description',
        'start_date',
        'end_date',
        'registration_deadline',
        'capacity',
        'requires_approval',
        'status',
        'form_schema',
        'created_by'
    ];

    protected $casts = [
        'form_schema' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_deadline' => 'datetime',
    ];

    public function moderators()
    {
        return $this->belongsToMany(User::class, 'event_moderators')->withTimestamps();
    }
}
