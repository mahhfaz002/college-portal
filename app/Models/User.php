<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Support\Permissions;
use App\Models\Concerns\BelongsToCollege;

class User extends Authenticatable
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
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'must_change_password' => 'boolean',
    ];
}
