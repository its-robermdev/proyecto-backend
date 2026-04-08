<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventFormSchemaValidationResource extends JsonResource
{
    /**
     * Serializes schema validation output for admin tooling endpoints.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'event_id' => $this['event_id'],
            'is_valid' => (bool) $this['is_valid'],
            'errors' => $this['errors'] ?? [],
        ];
    }
}

