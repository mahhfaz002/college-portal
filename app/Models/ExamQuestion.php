<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamQuestion extends Model
{
    protected $fillable = [
        'exam_id', 'question_text', 'option_a', 'option_b',
        'option_c', 'option_d', 'correct_option', 'marks', 'created_by',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}
