<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'college_id', 'user_id', 'title', 'body', 'audience', 'target_class', 'is_published',
        'target_department_id', 'target_program_id', 'target_level',
    ];

    protected $casts = ['is_published' => 'boolean'];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Announcements visible to a given role. For a student, a "students" notice
     * may be further narrowed to their department / course of study / level when
     * those targets are set on the announcement.
     */
    public function scopeVisibleTo($query, string $role, ?string $classArm = null, $student = null)
    {
        return $query->where('is_published', true)->where(function ($q) use ($role, $classArm, $student) {
            $q->whereIn('audience', ['all', 'both']);

            if ($role === 'student') {
                $q->orWhere(function ($s) use ($student) {
                    $s->where('audience', 'students');
                    if ($student) {
                        $s->where(fn ($w) => $w->whereNull('target_department_id')->orWhere('target_department_id', $student->department_id))
                          ->where(fn ($w) => $w->whereNull('target_program_id')->orWhere('target_program_id', $student->program_id))
                          ->where(fn ($w) => $w->whereNull('target_level')->orWhere('target_level', $student->level));
                    }
                });
                if ($classArm) {
                    $q->orWhere(fn ($qq) => $qq->where('audience', 'class')->where('target_class', $classArm));
                }
            } else {
                // Staff see staff/role-targeted and (for oversight) all class-targeted notices.
                $q->orWhere('audience', 'staff')
                  ->orWhere('audience', $role)
                  ->orWhere('audience', 'class');
            }
        });
    }
}
