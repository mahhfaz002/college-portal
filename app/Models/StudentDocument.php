<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class StudentDocument extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'college_id', 'student_id', 'applicant_id',
        'type', 'label', 'path', 'original_name',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
