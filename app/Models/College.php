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
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

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
