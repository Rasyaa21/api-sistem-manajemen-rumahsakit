<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'role' => $this->role,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Conditional relationships
            'patient' => $this->whenLoaded('patient', function () {
                return new PatientResource($this->patient);
            }),

            'doctor' => $this->whenLoaded('doctor', function () {
                return new DoctorResource($this->doctor);
            }),

            'doctor_applications' => $this->whenLoaded('doctorApplications', function () {
                return DoctorApplicationResource::collection($this->doctorApplications);
            }),
        ];
    }
}
