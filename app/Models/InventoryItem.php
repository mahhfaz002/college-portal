<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCollege;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use BelongsToCollege;

    protected $fillable = ['college_id', 'item_name', 'category', 'quantity', 'status', 'location'];
}
