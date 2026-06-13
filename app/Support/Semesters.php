<?php

namespace App\Support;

/**
 * Academic semesters a course can belong to. Courses are created per semester
 * by the Academic Secretary and filtered by it by HODs.
 */
class Semesters
{
    public const FIRST  = 'First Semester';
    public const SECOND = 'Second Semester';
    public const SUMMER = 'Summer Semester';

    public const ALL = [self::FIRST, self::SECOND, self::SUMMER];
}
