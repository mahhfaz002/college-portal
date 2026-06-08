<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetablePlan extends Model
{
    protected $fillable = ['term', 'session', 'status', 'params', 'engine', 'generated_by', 'approved_at'];

    protected $casts = [
        'params' => 'array',
        'approved_at' => 'datetime',
    ];

    public function entries()
    {
        return $this->hasMany(TimetableEntry::class, 'plan_id');
    }
}
