<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalRecordResource extends JsonResource
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
            'registration_id' => $this->registration_id,
            'diagnosis' => $this->diagnosis,
            'treatment' => $this->treatment,
            'additional_notes' => $this->additional_notes,
            'input_date' => $this->input_date?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Conditional relationships
            'registration' => $this->whenLoaded('registration', function () {
                return new RegistrationResource($this->registration);
            }),

            'medicines' => $this->whenLoaded('medicines', function () {
                return $this->medicines->map(function ($medicine) {
                    return [
                        'id' => $medicine->id,
                        'medicine_name' => $medicine->medicine_name,
                        'medicine_type' => $medicine->medicine_type,
                        'dosage' => $medicine->dosage,
                        'unit' => $medicine->unit,
                        'quantity' => $medicine->pivot->quantity,
                        'usage_instructions' => $medicine->pivot->usage_instructions,
                    ];
                });
            }),
        ];
    }
}
