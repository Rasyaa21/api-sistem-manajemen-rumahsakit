<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'specialization' => $this->specialization,
            'phone_number' => preg_replace('/(\d{4})(?=\d)/', '$1-', $this->phone_number),
            'practice_schedule' => $this->practice_schedule,
            'consultation_fee' => 'Rp ' . number_format($this->consultation_fee, 0, ',', '.'),
        ];
    }
}
