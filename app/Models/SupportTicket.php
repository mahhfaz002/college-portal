<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'college_id', 'user_id', 'subject', 'body', 'priority',
        'status', 'response', 'handled_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
