<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class Correspondence extends Model
{
    use BelongsToCollege;

    protected $fillable = ['college_id', 'ref_no', 'direction', 'subject', 'party', 'dated', 'status', 'notes'];

    protected $casts = ['dated' => 'date'];
}
