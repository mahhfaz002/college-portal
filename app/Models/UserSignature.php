<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSignature extends Model
{
    protected $fillable = ['user_id', 'data'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
