<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['student_id', 'fee_bill_id', 'amount', 'payment_method', 'description'];

    // This connects the payment to the student
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function feeBill()
    {
        return $this->belongsTo(FeeBill::class);
    }
}
