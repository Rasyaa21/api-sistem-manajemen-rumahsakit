<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecordMedicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_record_id',
        'medicine_id',
        'quantity',
        'usage_instructions',
    ];

    /**
     * Get the medical record for this pivot
     */
    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    /**
     * Get the medicine for this pivot
     */
    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }
}
