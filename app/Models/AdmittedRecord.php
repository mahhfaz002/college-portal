<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class AdmittedRecord extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'college_id', 'full_name', 'registration_number',
        'department', 'level', 'claimed_at', 'claimed_by',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
    ];

    public function isClaimed(): bool
    {
        return $this->claimed_at !== null;
    }
}
