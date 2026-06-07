<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    // These fields are allowed to be filled by our form
    protected $fillable = [
        'student_id',
        'subject_id',
        'ca_score',
        'exam_score',
        'term',
        'session',
        'grade',
        'total',
        'status',
        'exam_id',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    // Connects score back to a student
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // Connects score back to a subject
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
