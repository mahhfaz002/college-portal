<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Applicant extends Model
{
    protected $fillable = [
        'full_name',
        'date_of_birth',
        'gender',
        'parent_name',
        'parent_phone',
        'parent_email',
        'desired_class',
        'status',
        'reason',
        'passport_path',
        'birth_cert_path',
        'indigene_letter_path',
        'passport',
        'admission_number',
    ];
}
