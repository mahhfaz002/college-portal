<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A tenant. Each registered college brands and owns its own data.
 * The College model itself is NOT college-scoped (it is the tenant root).
 */
class College extends Model
{
    protected $fillable = [
        'name', 'acronym', 'logo_path', 'address', 'phone', 'email',
        'primary_color', 'paystack_public_key', 'paystack_secret_key',
        'registration_no_format', 'is_active',
        'domain', 'tagline', 'motto', 'about', 'established_year',
        'provost_name', 'provost_title', 'provost_message', 'provost_photo', 'key_dates',
        // Paystack marketplace / subaccount (settlement split per institution).
        'paystack_subaccount_code', 'paystack_subaccount_name', 'commission_percentage',
        'settlement_bank', 'settlement_account_number', 'settlement_account_name',
        'paystack_subaccount_status',
    ];

    protected function casts(): array
    {
        return [
            'is_active'             => 'boolean',
            'commission_percentage' => 'decimal:2',
            'key_dates'             => 'array',
        ];
    }

    /**
     * Whether this institution settles through its own Paystack subaccount
     * (the marketplace model) rather than a legacy standalone Paystack account.
     */
    public function usesSubaccount(): bool
    {
        return !empty($this->paystack_subaccount_code);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function programs()
    {
        return $this->hasMany(Program::class);
    }
}
