<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamEligibility extends Model
{
    protected $table = 'exam_eligibilities';

    protected $fillable = ['exam_id', 'student_id', 'status', 'reason', 'decided_by'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
