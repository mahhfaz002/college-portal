<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use BelongsToCollege;

    protected $fillable = ['college_id', 'name', 'acronym', 'description', 'section'];

    public function programs()
    {
        return $this->hasMany(Program::class);
    }

    public function gradingScheme()
    {
        return $this->hasMany(GradingScheme::class)->orderBy('sort')->orderByDesc('min_score');
    }
}
