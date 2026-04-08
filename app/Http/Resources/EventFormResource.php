<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventFormResource extends JsonResource
{
    /**
     * Serializes event form data in a stable API shape.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'event_id' => $this->id,
            'form_is_active' => (bool) $this->form_is_active,
            'form_schema' => $this->form_schema ?? [],
        ];
    }
}

