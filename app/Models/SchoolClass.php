<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    /**
     * The "classes" table holds the managed list of class arms
     * (e.g. JSS1A). Eloquent would pluralise this model to
     * "school_classes", so we pin the table name explicitly.
     */
    protected $table = 'classes';

    protected $fillable = ['name', 'level', 'section', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Teachers (staff users) assigned to this class.
     */
    public function teachers()
    {
        return $this->belongsToMany(User::class, 'class_teacher', 'class_id', 'user_id')->withTimestamps();
    }

    /**
     * Students currently in this class arm (matched by label).
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'class_arm', 'name');
    }
}
