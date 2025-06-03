<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationResource extends JsonResource
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
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'visit_date' => $this->visit_date?->format('Y-m-d'),
            'status' => $this->status,
            'complaint' => $this->complaint,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Conditional relationships
            'patient' => $this->whenLoaded('patient', function () {
                return new PatientResource($this->patient);
            }),

            'doctor' => $this->whenLoaded('doctor', function () {
                return new DoctorResource($this->doctor);
            }),

            'medical_record' => $this->whenLoaded('medicalRecord', function () {
                return new MedicalRecordResource($this->medicalRecord);
            }),
        ];
    }
}