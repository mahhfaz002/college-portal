<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassAttendanceLog extends Model
{
    protected $fillable = ['user_id', 'class_arm', 'log_date', 'present_count', 'total_count', 'taken_at'];

    protected $casts = [
        'log_date' => 'date',
        'taken_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
