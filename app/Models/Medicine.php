<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'medicine_name',
        'medicine_type',
        'dosage',
        'unit',
        'stock',
        'description',
    ];

    /**
     * Get the medical record medicines for this medicine
     */
    public function medicalRecordMedicines()
    {
        return $this->hasMany(MedicalRecordMedicine::class);
    }

    /**
     * Get the medical records that use this medicine
     */
    public function medicalRecords()
    {
        return $this->belongsToMany(MedicalRecord::class, 'medical_record_medicines')
                    ->withPivot('quantity', 'usage_instructions')
                    ->withTimestamps();
    }
}
