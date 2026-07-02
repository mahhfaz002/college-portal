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
        'signature_path',
    ];

    /**
     * Email is the account's unique identity — the login "username". Normalise it
     * to trimmed lowercase so the users.email UNIQUE index guarantees exactly one
     * account per address regardless of casing, and so login always matches.
     */
    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = is_string($value) ? strtolower(trim($value)) : $value;
    }

    public function departmentModel()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function signatureRecord()
    {
        return $this->hasOne(UserSignature::class);
    }

    /** Whether this user has an e-signature on file (DB or legacy disk). */
    public function hasSignature(): bool
    {
        return $this->signatureRecord()->exists()
            || ($this->signature_path && $this->signature_path !== 'db');
    }

    /**
     * The user's signature as a base64 data URI, ready to embed in a document
     * or stream. Prefers the database copy; falls back to a legacy file on the
     * documents disk for signatures saved before the DB migration.
     */
    public function signatureDataUri(): ?string
    {
        if ($rec = $this->signatureRecord) {
            return $rec->data;
        }

        if ($this->signature_path && $this->signature_path !== 'db') {
            $disk = config('filesystems.documents', 'local');
            try {
                if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($this->signature_path)) {
                    return 'data:image/png;base64,'.base64_encode(\Illuminate\Support\Facades\Storage::disk($disk)->get($this->signature_path));
                }
            } catch (\Throwable $e) {
                // ignore — no signature available
            }
        }

        return null;
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
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'notifications_last_read_at' => 'datetime',
        'password' => 'hashed',
        'must_change_password' => 'boolean',
    ];

    protected static function booted(): void
    {
        // This portal has NO open/self-service registration: every account is
        // created either by an admin (vouched) or after a CONFIRMED payment
        // (applicants via /apply, students via /student/register). Email
        // verification therefore adds no security — only friction — and it
        // silently broke once `email_verified_at` was dropped from $fillable
        // (so `User::create([... 'email_verified_at' => now()])` was a no-op and
        // paid applicants were stranded on /verify-email). Mark every new account
        // verified at creation so it reaches its dashboard immediately.
        //
        // We only backfill when `email_verified_at` was NEVER supplied. App code
        // reaches User::create() through mass assignment, which strips
        // `email_verified_at` (not in $fillable) — that's the broken case we fix.
        // Code that sets the attribute explicitly (e.g. a factory's unverified()
        // state, run unguarded) keeps full control, so the MustVerifyEmail gate
        // stays testable and usable if open registration is ever introduced.
        static::creating(function (self $user) {
            if (! array_key_exists('email_verified_at', $user->getAttributes())) {
                $user->email_verified_at = now();
            }
        });
    }
}
