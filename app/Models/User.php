<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Support\Permissions;
use App\Models\Concerns\BelongsToCollege;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, BelongsToCollege;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'surname',
        'email',
        'password',
        'role',
        'class_assigned',
        'subject_assigned',
        'must_change_password',
        'staff_id',
        'phone',
        'passport',
        'department',
        'employed_year',
        'next_of_kin_name',
        'next_of_kin_phone',
        'status',
        'college_id',
        'department_id',
        'program_id',
        'staff_category',
        'username',
        'platform_fee_paid',
        'qualification',
        'university',
        'class_of_degree',
        'address',
    ];

    public function departmentModel()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Classes this staff member is assigned to (many-to-many).
     */
    public function classes()
    {
        return $this->belongsToMany(SchoolClass::class, 'class_teacher', 'user_id', 'class_id')->withTimestamps();
    }

    /**
     * Subjects this staff member teaches (many-to-many).
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'subject_teacher', 'user_id', 'subject_id')->withTimestamps();
    }

    /**
     * Role helpers — single source of truth lives in App\Support\Permissions.
     */
    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isReadOnly(): bool
    {
        // Proprietor (owner) and Provost (academic head) are oversight roles:
        // they see everything in their college but change nothing.
        return in_array($this->role, ['proprietor', 'provost'], true);
    }

    public function canManage(string $capability): bool
    {
        return Permissions::roleCan($this->role, $capability);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_code',
    ];

    /**
     * Whether this account must clear email-OTP two-factor at login.
     * Mandatory for every staff role (anyone who isn't a student/applicant);
     * disabled wholesale when config('auth.two_factor_enabled') is false.
     */
    public function requiresTwoFactor(): bool
    {
        return config('auth.two_factor_enabled', true)
            && ! in_array($this->role, ['student', 'applicant'], true);
    }

    /**
     * Generate, store (hashed) and email a fresh 6-digit OTP valid for 10 minutes.
     */
    public function sendTwoFactorCode(): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'two_factor_code'       => \Illuminate\Support\Facades\Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(10),
        ])->save();

        $this->notify(new \App\Notifications\TwoFactorCodeNotification($code));
    }

    /** Verify a submitted OTP; clears it on success. */
    public function verifyTwoFactorCode(string $code): bool
    {
        if (! $this->two_factor_code || ! $this->two_factor_expires_at || now()->greaterThan($this->two_factor_expires_at)) {
            return false;
        }

        if (! \Illuminate\Support\Facades\Hash::check($code, $this->two_factor_code)) {
            return false;
        }

        $this->forceFill(['two_factor_code' => null, 'two_factor_expires_at' => null])->save();

        return true;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'must_change_password' => 'boolean',
        'two_factor_expires_at' => 'datetime',
    ];
}
