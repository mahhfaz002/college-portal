<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

/**
 * Exam Mode for a college. The submission deadline is always 5 days before the
 * exam start. Only one cycle is "active" per college at a time.
 */
class ExamCycle extends Model
{
    use BelongsToCollege;

    /** Question-submission window closes this many days before exams start. */
    public const SUBMISSION_LEAD_DAYS = 5;

    protected $fillable = [
        'college_id', 'title', 'exam_start_at', 'submission_deadline_at', 'status', 'created_by',
    ];

    protected $casts = [
        'exam_start_at'          => 'datetime',
        'submission_deadline_at' => 'datetime',
    ];

    /** The current college's active exam cycle (if any), college-scoped. */
    public static function active(): ?self
    {
        return static::where('status', 'active')->latest('exam_start_at')->first();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
