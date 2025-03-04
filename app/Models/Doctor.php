<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Doctor extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'name',
        'specialization',
        'phone_number',
        'practice_schedule',
        'consultation_fee',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}
