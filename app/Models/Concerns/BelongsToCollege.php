<?php

namespace App\Models\Concerns;

use App\Models\Scopes\CollegeScope;

/**
 * Multi-tenant isolation by college.
 *
 * Any model using this trait is automatically constrained to the currently
 * resolved college (see current_college_id()): every query is filtered by
 * college_id, and new records are stamped with the current college on create.
 *
 * This means no controller, report, or hand-written query through the model
 * can ever leak rows belonging to another college — isolation is enforced at
 * the data layer, not left to each call site to remember.
 */
trait BelongsToCollege
{
    public static function bootBelongsToCollege(): void
    {
        static::addGlobalScope(new CollegeScope());

        static::creating(function ($model) {
            if (empty($model->college_id) && ($cid = current_college_id()) !== null) {
                $model->college_id = $cid;
            }
        });
    }

    public function college()
    {
        return $this->belongsTo(\App\Models\College::class);
    }
}
