<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use BelongsToCollege;

    protected $fillable = ['isbn', 'title', 'author', 'category', 'total_copies', 'available_copies', 'college_id'];

    public function borrowRecords()
    {
        return $this->hasMany(BorrowRecord::class);
    }
}
