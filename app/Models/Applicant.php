<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class Applicant extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'full_name', 'date_of_birth', 'gender',
        'parent_name', 'parent_phone', 'parent_email',
        'desired_class', 'status', 'reason',
        'passport_path', 'birth_cert_path', 'indigene_letter_path',
        'passport', 'admission_number', 'address', 'section',
        'fslc_path', 'junior_waec_path',
        // Phase 2
        'college_id', 'user_id',
        'first_name', 'surname', 'other_name', 'phone', 'email',
        'first_choice_program_id', 'second_choice_program_id',
        'guardian_name', 'guardian_relationship', 'guardian_phone',
        'guardian_email', 'guardian_address', 'guardian_occupation',
        'sponsor_name', 'sponsor_relationship', 'sponsor_phone', 'sponsor_address',
        'application_status', 'payment_status',
        'admitted_program_id', 'admission_response',
    ];

    protected $casts = ['date_of_birth' => 'date'];

    public function age(): ?int
    {
        return $this->date_of_birth ? (int) $this->date_of_birth->age : null;
    }

    public function firstChoice()
    {
        return $this->belongsTo(Program::class, 'first_choice_program_id');
    }

    public function secondChoice()
    {
        return $this->belongsTo(Program::class, 'second_choice_program_id');
    }

    public function admittedProgram()
    {
        return $this->belongsTo(Program::class, 'admitted_program_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
