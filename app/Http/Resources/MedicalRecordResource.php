<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_consultation' => $this->id_consultation,
            'diagnosis' => $this->diagnosis,
            'treatment' => $this->treatment,
            'examination_date' => $this->examination_date,
        ];
    }
}
