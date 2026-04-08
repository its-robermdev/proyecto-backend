<?php

namespace App\Rules;

use App\Models\Event;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EventHasAvailableSpots implements ValidationRule
{
    public const MESSAGE = "El evento ha alcanzado su capacidad m\u{00E1}xima y no acepta m\u{00E1}s postulaciones por el momento";

    public function __construct(
        private readonly Event $event,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->event->hasAvailableSpots()) {
            return;
        }

        $fail(self::MESSAGE);
    }
}
