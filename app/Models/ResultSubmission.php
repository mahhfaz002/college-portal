<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class ResultSubmission extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'college_id',
        'subject_id',
        'user_id',
        'term',
        'session',
        'scan_path',
        'submitted_at',
        'physical_copy_deadline',
        'status',
        'transmitted_at',
        'transmitted_by',
    ];

    protected $casts = [
        'submitted_at'          => 'datetime',
        'physical_copy_deadline' => 'datetime',
        'transmitted_at'        => 'datetime',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transmitter()
    {
        return $this->belongsTo(User::class, 'transmitted_by');
    }

    public function isTransmitted(): bool
    {
        return $this->transmitted_at !== null;
    }
}
