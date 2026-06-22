<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'college_id', 'subject_id', 'exam_cycle_id', 'title', 'term', 'session', 'class_arms',
        'level', 'instructions_objective', 'instructions_theory',
        'duration_minutes', 'access_password', 'status', 'created_by', 'submitted_at',
        'hod_feedback', 'reviewed_by', 'reviewed_at', 'release_at',
    ];

    protected $casts = [
        'class_arms'   => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at'  => 'datetime',
        'release_at'   => 'datetime',
    ];

    /** Locked once the lecturer forwards questions to the exam officer. */
    public function isLocked(): bool
    {
        return $this->submitted_at !== null;
    }

    /**
     * Visible to the Exam Officer only once the HOD has approved it AND any
     * scheduled release time has arrived (null release = available immediately).
     * The schedule is what prevents the paper leaking to the officer too early.
     */
    public function isReleasedToOfficer(): bool
    {
        return $this->status === 'approved'
            && ($this->release_at === null || $this->release_at->lessThanOrEqualTo(now()));
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function examCycle()
    {
        return $this->belongsTo(ExamCycle::class);
    }

    public function questions()
    {
        return $this->hasMany(ExamQuestion::class);
    }

    public function submissions()
    {
        return $this->hasMany(ExamSubmission::class);
    }

    public function eligibilities()
    {
        return $this->hasMany(ExamEligibility::class);
    }

    public function totalMarks(): int
    {
        return (int) $this->questions()->sum('marks');
    }
}
