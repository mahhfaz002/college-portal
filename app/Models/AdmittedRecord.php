<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class AdmittedRecord extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'college_id', 'full_name', 'registration_number',
        'department', 'level', 'claimed_at', 'claimed_by',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
    ];

    public function isClaimed(): bool
    {
        return $this->claimed_at !== null;
    }

    /** The user account created from this record (set when claimed). */
    public function claimer()
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    /**
     * Onboarding status for the super-admin list:
     *  - "registered": an account was created AND the platform fee was paid;
     *  - "pending":    not yet claimed, or claimed but the fee is still unpaid.
     */
    public function status(): string
    {
        return $this->claimed_at !== null && optional($this->claimer)->platform_fee_paid
            ? 'registered'
            : 'pending';
    }
}
