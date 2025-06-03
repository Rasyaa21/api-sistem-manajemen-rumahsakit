<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
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
            'license_number' => $this->license_number,
            'specialization' => $this->specialization,
            'practice_schedule' => $this->practice_schedule,
            'consultation_fee' => (float) $this->consultation_fee,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Conditional relationships
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'full_name' => $this->user->full_name,
                    'email' => $this->user->email,
                    'phone_number' => $this->user->phone_number,
                ];
            }),

            'registrations' => $this->whenLoaded('registrations', function () {
                return RegistrationResource::collection($this->registrations);
            }),

            'reports' => $this->whenLoaded('reports', function () {
                return ReportResource::collection($this->reports);
            }),
        ];
    }
}