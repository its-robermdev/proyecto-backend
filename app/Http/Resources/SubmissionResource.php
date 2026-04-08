<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'submitted_by_email' => $this->submitted_by_email,
            'submitted_by_name' => $this->submitted_by_name,
            'participation_type' => $this->participation_type,
            'team_name' => $this->team_name,
            'status' => $this->status,
            'review_comment' => $this->review_comment,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'form_answers' => $this->form_answers,
            'event' => $this->whenLoaded('event', fn (): array => [
                'id' => $this->event->id,
                'title' => $this->event->title,
                'slug' => $this->event->slug,
            ]),
            'reviewer' => $this->whenLoaded('reviewer', fn (): ?array => $this->reviewer === null ? null : [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
                'email' => $this->reviewer->email,
            ]),
            'members' => $this->whenLoaded('members', fn () => $this->members->map(fn ($member): array => [
                'id' => $member->id,
                'full_name' => $member->full_name,
                'email' => $member->email,
                'is_captain' => $member->is_captain,
            ])->values()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
