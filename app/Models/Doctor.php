<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'license_number',
        'specialization',
        'practice_schedule',
        'consultation_fee',
    ];

    protected $casts = [
        'consultation_fee' => 'decimal:2',
    ];

    /**
     * Get the user that owns the doctor profile
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the registrations for this doctor
     */
    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Get the reports for this doctor
     */
    public function reports()
    {
        return $this->hasMany(Report::class, 'doctor_id');
    }
}
