<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_id',
        'diagnosis',
        'treatment',
        'additional_notes',
        'input_date',
    ];

    protected $casts = [
        'input_date' => 'datetime',
    ];

    /**
     * Get the registration for this medical record
     */
    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * Get the medical record medicines for this medical record
     */
    public function medicalRecordMedicines()
    {
        return $this->hasMany(MedicalRecordMedicine::class);
    }

    /**
     * Get the medicines for this medical record
     */
    public function medicines()
    {
        return $this->belongsToMany(Medicine::class, 'medical_record_medicines')
                    ->withPivot('quantity', 'usage_instructions')
                    ->withTimestamps();
    }
}
