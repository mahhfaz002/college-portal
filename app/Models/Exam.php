<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'subject_id', 'title', 'term', 'session', 'class_arms',
        'duration_minutes', 'access_password', 'status', 'created_by',
    ];

    protected $casts = [
        'class_arms' => 'array',
    ];

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
