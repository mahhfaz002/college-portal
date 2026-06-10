<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCollege;

class FeeBill extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'student_id', 'title', 'term', 'session',
        'amount', 'amount_paid', 'status', 'created_by', 'college_id',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getBalanceAttribute(): float
    {
        return max(0, (float) $this->amount - (float) $this->amount_paid);
    }

    /**
     * Recompute paid/part/unpaid from amount_paid and persist.
     */
    public function refreshStatus(): void
    {
        $this->status = match (true) {
            $this->amount_paid >= $this->amount => 'paid',
            $this->amount_paid > 0              => 'part',
            default                             => 'unpaid',
        };
        $this->save();
    }
}
