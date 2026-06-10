<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class FeeOrder extends Model
{
    use BelongsToCollege;

    protected $fillable = [
        'college_id', 'created_by', 'title', 'description', 'amount',
        'scope_type', 'scope_label', 'students_count',
    ];

    protected $casts = ['amount' => 'decimal:2'];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function paidCount(): int
    {
        return $this->invoices()->where('status', 'paid')->count();
    }

    public function collected()
    {
        return $this->invoices()->where('status', 'paid')->sum('amount');
    }
}
