<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_doctor' => $this->id_doctor,
            'patient_count' => $this->patient_count,
            'income' => 'Rp ' . number_format($this->income, 0, ',', '.'),
            'report_date' => $this->report_date->format('Y-m-d'),
        ];

    }
}
