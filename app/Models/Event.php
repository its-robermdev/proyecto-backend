<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
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
        'allows_teams',
        'form_is_active',
        'status',
        'form_schema',
        'created_by',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'allows_teams' => 'boolean',
        'form_is_active' => 'boolean',
        'form_schema' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_deadline' => 'datetime',
    ];

    // Moderadores/responsables asignados al evento.
    public function moderators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_moderators')->withTimestamps();
    }

    // Inscripciones enviadas al evento.
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    // Usuario administrador que creó el evento.
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
