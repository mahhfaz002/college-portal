<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class BorrowRecord extends Model
{
    use BelongsToCollege;

    protected $fillable = ['student_id', 'book_id', 'borrowed_at', 'due_at', 'returned_at', 'fine_amount', 'college_id'];

    protected $casts = [
        'borrowed_at' => 'datetime',
        'due_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
