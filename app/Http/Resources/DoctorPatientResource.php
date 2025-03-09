<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorPatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'specialization' => $this->specialization,
            'phone_number' => preg_replace('/(\d{4})(?=\d)/', '$1-', $this->phone_number),
            'practice_schedule' => $this->practice_schedule,
            'consultation_fee' => 'Rp ' . number_format($this->consultation_fee, 0, ',', '.'),
        ];
    }
}
