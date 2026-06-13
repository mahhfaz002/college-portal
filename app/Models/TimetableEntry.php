<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetableEntry extends Model
{
    protected $fillable = [
        'plan_id', 'class_arm', 'program_id', 'level', 'day', 'period_no',
        'start_time', 'end_time', 'subject_id', 'teacher_id',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function plan()
    {
        return $this->belongsTo(TimetablePlan::class, 'plan_id');
    }
}
