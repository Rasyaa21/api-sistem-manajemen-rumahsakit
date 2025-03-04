<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'birth_date' => $this->birth_date,
            'address' => $this->address,
            'phone_number' => $this->phone_number,
            'medical_history' => $this->medical_history,
            'email' => $this->email,
        ];
    }
}
