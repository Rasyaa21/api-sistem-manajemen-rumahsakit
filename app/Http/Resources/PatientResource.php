<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
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
            'national_id' => $this->national_id,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'gender' => $this->gender,
            'address' => $this->address,
            'blood_type' => $this->blood_type,
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
        ];
    }
}