<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class StudentAffairsRegister extends Model
{
    use BelongsToCollege;

    protected $table = 'student_affairs_register';

    protected $fillable = [
        'college_id',
        'student_id',
        'registered_by',
        'checklist',
        'notes',
        'registered_at',
    ];

    protected $casts = [
        'checklist'     => 'array',
        'registered_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function registeredByUser()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
