<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use BelongsToCollege;

    protected $fillable = ['college_id', 'user_id', 'title', 'body', 'audience', 'target_class', 'is_published'];

    protected $casts = ['is_published' => 'boolean'];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Announcements visible to a given role (and class, for students).
     * audience: all | staff | students | both | class (+ legacy role names).
     */
    public function scopeVisibleTo($query, string $role, ?string $classArm = null)
    {
        return $query->where('is_published', true)->where(function ($q) use ($role, $classArm) {
            $q->whereIn('audience', ['all', 'both']);

            if ($role === 'student') {
                $q->orWhere('audience', 'students');
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
