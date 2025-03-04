<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_consultation',
        'diagnosis',
        'treatment',
        'examination_date',
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }
}
