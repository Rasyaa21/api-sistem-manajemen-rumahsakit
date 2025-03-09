<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "id_doctor" => $this->id_doctor,
            "id_patient" => $this->id_patient,
            "consultation_date" => $this->consultation_date,
            "consultation_time" => $this->consultation_time,
        ];
    }
}

