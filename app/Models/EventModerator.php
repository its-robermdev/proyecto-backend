<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventModerator extends Model
{
    /** @use HasFactory<\Database\Factories\EventModeratorFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
    ];

    // Relación al evento moderado.
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    // Relación al usuario moderador.
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
