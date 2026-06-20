<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class ChangeOfCourseRequest extends Model
{
    use BelongsToCollege;

    /** Non-refundable change-of-course application fee (Naira). */
    public const FEE = 25000;

    protected $fillable = [
        'college_id', 'student_id', 'user_id',
        'current_program_id', 'requested_program_id', 'reason',
        'invoice_id', 'status',
        'secretary_id', 'secretary_note', 'recommended_at',
        'registrar_id', 'registrar_reason', 'decided_at',
    ];

    protected $casts = [
        'recommended_at' => 'datetime',
        'decided_at'     => 'datetime',
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

    /** Human-readable status label + colour for the tracking timeline. */
    public function statusLabel(): string
    {
        return [
            'pending_payment' => 'Awaiting payment',
            'under_review'    => 'Under Academic Secretary review',
            'recommended'     => 'Recommended — awaiting Registrar',
            'not_recommended' => 'Not recommended — awaiting Registrar',
            'approved'        => 'Approved',
            'rejected'        => 'Rejected',
        ][$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function isPaid(): bool
    {
        return ! in_array($this->status, ['pending_payment'], true);
    }
}
