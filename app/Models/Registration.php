<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'visit_date',
        'status',
        'complaint',
    ];

    protected $casts = [
        'visit_date' => 'date',
    ];

    /**
     * Get the patient for this registration
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Get the doctor for this registration
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the medical records for this registration
     */
    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class);
    }
}
