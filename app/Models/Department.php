<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use BelongsToCollege;

    protected $fillable = ['college_id', 'name', 'acronym', 'description'];

    public function programs()
    {
        return $this->hasMany(Program::class);
    }
}
