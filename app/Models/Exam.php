<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'subject_id', 'title', 'term', 'session', 'class_arms',
        'duration_minutes', 'access_password', 'status', 'created_by', 'submitted_at',
    ];

    protected $casts = [
        'class_arms'   => 'array',
        'submitted_at' => 'datetime',
    ];

    /** Locked once the lecturer forwards questions to the exam officer. */
    public function isLocked(): bool
    {
        return $this->submitted_at !== null;
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function questions()
    {
        return $this->hasMany(ExamQuestion::class);
    }

    public function submissions()
    {
        return $this->hasMany(ExamSubmission::class);
    }

    public function eligibilities()
    {
        return $this->hasMany(ExamEligibility::class);
    }

    public function totalMarks(): int
    {
        return (int) $this->questions()->sum('marks');
    }
}
