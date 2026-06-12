<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class GradingScheme extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'college_id', 'department_id', 'grade', 'min_score', 'max_score', 'remark', 'sort',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /** Resolve a numeric score to a grade band for a department. */
    public static function gradeFor(int $departmentId, int $score): ?self
    {
        return static::where('department_id', $departmentId)
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->orderByDesc('min_score')
            ->first();
    }
}
