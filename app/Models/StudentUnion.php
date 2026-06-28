<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class StudentUnion extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'college_id', 'name', 'acronym', 'year_established',
        'constituents', 'members_count', 'status', 'created_by',
    ];

    public function leaders()
    {
        return $this->hasMany(StudentUnionLeader::class)->orderBy('id');
    }

    /** The president (or the first leader) for the list view. */
    public function president()
    {
        return $this->leaders->first(fn ($l) => stripos((string) $l->position, 'president') !== false)
            ?? $this->leaders->first();
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }
}
