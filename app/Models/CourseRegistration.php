<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class CourseRegistration extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'college_id',
        'student_id',
        'subject_id',
        'term',
        'session',
        'is_carryover',
        'registered_at',
        'dropped_at',
    ];

    protected $casts = [
        'is_carryover'  => 'boolean',
        'registered_at' => 'datetime',
        'dropped_at'    => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function isActive(): bool
    {
        return $this->dropped_at === null;
    }
}
