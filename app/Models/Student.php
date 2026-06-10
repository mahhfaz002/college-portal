<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCollege;

class Student extends Model
{
    use HasFactory, BelongsToCollege;

    protected $fillable = [
        'full_name',
        'email',
        'admission_number',
        'class_arm',
        'section',
        'parent_phone',
        'fees_balance',
        'blood_group',
        'photo',
        'college_id',
        'department_id',
        'program_id',
        'level',
        'registration_status',
        'registration_number',
        'applicant_id',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
    public function payments()
{
    return $this->hasMany(Payment::class);
}
public function scores()
{
    return $this->hasMany(Score::class);
}
public function attendances()
{
    return $this->hasMany(Attendance::class);
}

public function bills()
{
    return $this->hasMany(FeeBill::class);
}

/**
 * Whether the student has settled all fees (used for exam eligibility).
 */
public function feesCleared(): bool
{
    return (float) $this->fees_balance <= 0;
}

}
