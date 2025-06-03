<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'full_name' => $this->full_name,
            'national_id' => $this->national_id,
            'license_number' => $this->license_number,
            'specialization' => $this->specialization,
            'cv_url' => $this->cv_url,
            'diploma_url' => $this->diploma_url,
            'application_status' => $this->application_status,
            'admin_notes' => $this->admin_notes,
            'processed_at' => $this->processed_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Conditional relationships
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'full_name' => $this->user->full_name,
                    'email' => $this->user->email,
                    'phone_number' => $this->user->phone_number,
                    'role' => $this->user->role,
                ];
            }),
        ];
    }
}
