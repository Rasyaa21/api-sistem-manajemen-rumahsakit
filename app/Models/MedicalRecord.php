<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $table = 'medical_records';
    protected $primaryKey = 'id_record';

    protected $fillable = [
        'id_consultation',
        'diagnosis',
        'treatment',
        'examination_date',
    ];

    public function consultation()
    {
        return $this->belongsTo(Consultation::class, 'id_consultation');
    }
}
