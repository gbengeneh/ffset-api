<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompetitionRegistrationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'competition_id' => $this->competition_id,
            'competition' => $this->whenLoaded('competition'),
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'gamertag' => $this->gamertag,
            'game' => $this->game,
            'state' => $this->state,
            'payment_status' => $this->payment_status,
            'sale_id' => $this->sale_id,
            // Only revealed once an admin approves (marks payment_status paid) —
            // this is the ID that was sent to Telegram at registration time.
            'reference_code' => $this->when($this->payment_status === 'paid', $this->reference_code),
            'created_at' => $this->created_at,
        ];
    }
}
