<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope that limits every query to the currently resolved college.
 *
 * When current_college_id() is null (e.g. the public landing page, console
 * commands, or the not-yet-assigned super admin) the scope is a no-op so
 * seeding and global tooling still work.
 */
class CollegeScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // IMPORTANT: resolve ONLY from the request-bound context, never auth().
        // The User model is college-scoped, and calling auth() here during login
        // (retrieveById) would re-enter the guard and recurse infinitely.
        // SetCollegeContext binds this value after the session/user is resolved.
        $collegeId = app()->bound('current_college_id') ? app('current_college_id') : null;

        if ($collegeId !== null) {
            $builder->where($model->getTable() . '.college_id', $collegeId);
        }
    }
}
