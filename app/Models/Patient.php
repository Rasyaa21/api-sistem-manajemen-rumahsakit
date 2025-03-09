<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Patient extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'email',
        'password',
        'name',
        'birth_date',
        'address',
        'phone_number',
        'medical_history',
    ];

    protected $hidden = [
        'password',
    ];

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }
}
