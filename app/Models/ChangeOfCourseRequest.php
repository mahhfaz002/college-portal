<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class ChangeOfCourseRequest extends Model
{
    use BelongsToCollege;

    /** Non-refundable change-of-course application fee (Naira). */
    public const FEE = 25000;

    /**
     * Pipeline statuses. Everything between secretary_review and registrar_review
     * reads as "Processing" to the student; approved/rejected/completed are final.
     */
    public const PROCESSING_STATUSES = [
        'secretary_review',
        'new_hod_review', 'new_hod_approved', 'new_hod_rejected',
        'current_hod_review', 'current_hod_approved', 'current_hod_rejected',
        'registrar_review',
    ];

    protected $fillable = [
        'college_id', 'student_id', 'user_id',
        'current_program_id', 'requested_program_id', 'reason',
        'invoice_id', 'status',
        'secretary_id', 'secretary_note', 'secretary_comment', 'recommended_at',
        'new_hod_id', 'new_hod_decision', 'new_hod_comment', 'new_hod_at',
        'current_hod_id', 'current_hod_decision', 'current_hod_comment', 'current_hod_at',
        'registrar_id', 'registrar_reason', 'registrar_comment', 'decided_at',
        'rejection_reason', 'rejected_stage',
        'forwarded_to_new_hod_at', 'forwarded_to_current_hod_at', 'forwarded_to_registrar_at',
        'new_registration_invoice_id', 'new_fee_paid_at', 'migrated_at',
    ];

    protected $casts = [
        'recommended_at'              => 'datetime',
        'decided_at'                  => 'datetime',
        'new_hod_at'                  => 'datetime',
        'current_hod_at'              => 'datetime',
        'forwarded_to_new_hod_at'     => 'datetime',
        'forwarded_to_current_hod_at' => 'datetime',
        'forwarded_to_registrar_at'   => 'datetime',
        'new_fee_paid_at'             => 'datetime',
        'migrated_at'                 => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function currentProgram()
    {
        return $this->belongsTo(Program::class, 'current_program_id');
    }

    public function requestedProgram()
    {
        return $this->belongsTo(Program::class, 'requested_program_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function newHod()
    {
        return $this->belongsTo(User::class, 'new_hod_id');
    }

    public function currentHod()
    {
        return $this->belongsTo(User::class, 'current_hod_id');
    }

    /** Human-readable status label for the staff timeline. */
    public function statusLabel(): string
    {
        return [
            'pending_payment'     => 'Awaiting payment',
            'secretary_review'    => 'Academic Secretary processing',
            'new_hod_review'      => 'With new department HOD',
            'new_hod_approved'    => 'New HOD accepted — with Academic Secretary',
            'new_hod_rejected'    => 'New HOD rejected — with Academic Secretary',
            'current_hod_review'  => 'With current department HOD',
            'current_hod_approved'=> 'Current HOD accepted — with Academic Secretary',
            'current_hod_rejected'=> 'Current HOD rejected — with Academic Secretary',
            'registrar_review'    => 'Awaiting Registrar approval',
            'approved'            => 'Approved',
            'rejected'            => 'Rejected',
            'completed'           => 'Completed — migrated to new course',
        ][$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    /** What the STUDENT sees: processing (yellow) / processed (green). */
    public function studentStage(): string
    {
        if ($this->status === 'pending_payment') {
            return 'unpaid';
        }
        if (in_array($this->status, self::PROCESSING_STATUSES, true)) {
            return 'processing';
        }
        return 'processed'; // approved | rejected | completed
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'completed'], true);
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isPaid(): bool
    {
        return $this->status !== 'pending_payment';
    }

    /** Approved but the student has not yet paid the new-course registration fee. */
    public function awaitingNewRegistrationFee(): bool
    {
        return $this->status === 'approved' && ! $this->new_fee_paid_at;
    }
}
