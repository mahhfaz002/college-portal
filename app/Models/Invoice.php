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
        'purpose', 'description', 'amount', 'convenience_fee', 'service_fee', 'currency',
        'status', 'reference', 'gateway_reference', 'payment_method',
        'payer_email', 'paid_at', 'meta',
        // Paystack settlement split (recorded at verification time).
        'platform_commission', 'institution_share', 'settlement_status',
        'settlement_reference', 'settlement_at', 'gateway_response',
    ];

    protected $casts = [
        'amount'               => 'decimal:2',
        'convenience_fee'      => 'decimal:2',
        'service_fee'          => 'decimal:2',
        'platform_commission'  => 'decimal:2',
        'institution_share'    => 'decimal:2',
        'paid_at'              => 'datetime',
        'settlement_at'        => 'datetime',
        'meta'                 => 'array',
        'gateway_response'     => 'array',
    ];

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * The grand total actually charged at the gateway:
     * the fee itself plus the portal convenience fee plus the Paystack
     * processing fee. Both surcharges are 0 until the checkout sets them.
     */
    public function chargeable(): float
    {
        return (float) $this->amount + (float) $this->convenience_fee + (float) $this->service_fee;
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
