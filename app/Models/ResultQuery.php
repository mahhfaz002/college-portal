<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultQuery extends Model
{
    protected $fillable = [
        'student_id', 'score_id', 'message',
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
