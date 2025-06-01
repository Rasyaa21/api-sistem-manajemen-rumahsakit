<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'national_id',
        'license_number',
        'specialization',
        'cv_url',
        'diploma_url',
        'application_status',
        'admin_notes',
        'application_date',
    ];

    protected $casts = [
        'application_date' => 'datetime',
    ];

    /**
     * Get the user that submitted this application
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
