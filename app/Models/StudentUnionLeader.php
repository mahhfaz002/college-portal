<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentUnionLeader extends Model
{
    protected $fillable = [
        'student_union_id', 'name', 'department', 'course_of_study',
        'level', 'position', 'tenure_start', 'tenure_end',
    ];

    protected $casts = [
        'tenure_start' => 'date',
        'tenure_end'   => 'date',
    ];

    public function union()
    {
        return $this->belongsTo(StudentUnion::class, 'student_union_id');
    }
}
