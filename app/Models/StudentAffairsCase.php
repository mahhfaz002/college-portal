<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class StudentAffairsCase extends Model
{
    use BelongsToCollege;

    protected $table = 'student_affairs_cases';

    protected $fillable = [
        'college_id', 'student_id', 'student_name', 'category', 'description', 'status', 'logged_by',
        'recommendation', 'penalty_type', 'resolution', 'resolved_by', 'resolution_date',
        'forwarded_to_registrar_at', 'forwarded_to_provost_at',
        'registrar_resolution', 'provost_resolution', 'final_resolution', 'student_notified_at',
    ];

    protected $casts = [
        'resolution_date'           => 'datetime',
        'forwarded_to_registrar_at' => 'datetime',
        'forwarded_to_provost_at'   => 'datetime',
        'student_notified_at'       => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function loggedByUser()
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function resolvedByUser()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
