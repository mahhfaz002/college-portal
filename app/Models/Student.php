<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'email',
        'admission_number',
        'class_arm',
        'parent_phone',
        'fees_balance',
        'blood_group',
        'photo'
    ];
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
