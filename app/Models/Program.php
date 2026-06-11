<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

/**
 * A program of study sits under a department. One department -> many programs.
 * Fees (application/acceptance/registration) are defined per program and drive
 * the admissions & finance workflow in later phases.
 */
class Program extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'college_id', 'department_id', 'name', 'acronym',
        'application_fee', 'acceptance_fee', 'registration_fee',
        'level_system', 'duration_years', 'program_type', 'levels', 'id_format',
    ];

    protected $casts = [
        'application_fee'  => 'decimal:2',
        'acceptance_fee'   => 'decimal:2',
        'registration_fee' => 'decimal:2',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
