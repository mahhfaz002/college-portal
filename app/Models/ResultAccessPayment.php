<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class ResultAccessPayment extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'college_id',
        'student_id',
        'term',
        'session',
        'invoice_id',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
