<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCollege;

/**
 * Subject == Course in the tertiary model. The UI labels these "Courses"
 * (course title, course code, course unit) under a department + program.
 */
class Subject extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'name', 'section', 'college_id',
        'course_code', 'course_unit', 'department_id', 'program_id', 'level', 'semester',
    ];

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'subject_teacher', 'subject_id', 'user_id')->withTimestamps();
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Relationship: A subject can have many scores
     */
    public function scores()
    {
        return $this->hasMany(Score::class);
    }
}
