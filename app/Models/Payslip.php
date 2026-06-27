<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'college_id', 'user_id', 'month', 'basic_salary', 'allowances', 'deductions',
        'tax', 'net_salary', 'status', 'flag_comment', 'created_by',
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

    /**
     * net = basic + allowances - deductions - tax.
     */
    public function recomputeNet(): void
    {
        $this->net_salary = max(0,
            (float) $this->basic_salary
            + (float) $this->allowances
            - $this->totalDeductions()
            - (float) $this->tax
        );
    }
}
