<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'college_id', 'user_id', 'month', 'basic_salary', 'allowances', 'deductions',
        'tax', 'contributory_savings', 'net_salary', 'status', 'flag_comment', 'created_by',
        'submitted_at', 'approved_at', 'paid_at',
        'provost_status', 'provost_comment', 'provost_reviewed_at',
        'proprietor_status', 'proprietor_comment', 'proprietor_approved_at',
    ];

    protected $casts = [
        'deductions'            => 'array',
        'submitted_at'          => 'datetime',
        'approved_at'           => 'datetime',
        'paid_at'               => 'datetime',
        'provost_reviewed_at'   => 'datetime',
        'proprietor_approved_at' => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function totalDeductions(): float
    {
        return collect($this->deductions ?? [])->sum(fn ($d) => (float) ($d['amount'] ?? 0));
    }

    /** Gross earnings — the base for the percentage-based tax and savings. */
    public function gross(): float
    {
        return (float) $this->basic_salary + (float) $this->allowances;
    }

    /** Tax is stored as a percentage of gross earnings. */
    public function taxAmount(): float
    {
        return round($this->gross() * ((float) $this->tax / 100), 2);
    }

    /** Mandatory contributory savings, a percentage of gross earnings. */
    public function savingsAmount(): float
    {
        return round($this->gross() * ((float) $this->contributory_savings / 100), 2);
    }

    /**
     * net = gross − manual deductions − tax(%) − contributory savings(%).
     */
    public function recomputeNet(): void
    {
        $this->net_salary = max(0,
            $this->gross()
            - $this->totalDeductions()
            - $this->taxAmount()
            - $this->savingsAmount()
        );
    }
}
