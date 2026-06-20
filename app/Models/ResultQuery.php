<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class ResultQuery extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'college_id', 'student_id', 'score_id', 'message',
        'resolution', 'status', 'resolved_by',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function score()
    {
        return $this->belongsTo(Score::class);
    }
}
