<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class StudentAffairsCase extends Model
{
    use BelongsToCollege;

    protected $table = 'student_affairs_cases';

    protected $fillable = ['college_id', 'student_id', 'student_name', 'category', 'description', 'status', 'logged_by'];
}
