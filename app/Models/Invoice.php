<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

/**
 * An online-payment invoice settled via Paystack. Reused across the application
 * fee (Phase 2) and acceptance / registration / general fees (Phase 3-4).
 */
class Invoice extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'college_id', 'applicant_id', 'student_id', 'user_id', 'program_id',
        'fee_order_id',
        'purpose', 'description', 'amount', 'currency',
        'status', 'reference', 'gateway_reference', 'payment_method',
        'payer_email', 'paid_at', 'meta',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
        'meta'    => 'array',
    ];

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
