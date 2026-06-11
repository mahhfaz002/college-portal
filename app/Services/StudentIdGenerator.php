<?php

namespace App\Services;

use App\Models\College;
use App\Models\Program;
use App\Models\Student;

/**
 * Builds a student/registration number following the rule:
 *   collegeAcronym / admissionYear / programType (UG|DIP|CERT) / courseAcronym / serial
 * e.g.  MAHHFAZ/2026/UG/SLT/0001
 *
 * The format is configurable per course of study (program). Tokens:
 *   {acronym} {year} {type} {program} {dept} {serial}
 * The serial is per (program, year).
 */
class StudentIdGenerator
{
    public function generate(Program $program, ?int $year = null): string
    {
        $year ??= (int) date('Y');
        $college = current_college() ?? College::find($program->college_id);
        $program->loadMissing('department');

        // Per-program format takes precedence, then the college default.
        $format = $program->id_format
            ?: ($college?->registration_no_format ?: '{acronym}/{year}/{type}/{program}/{serial}');

        $serial = Student::where('program_id', $program->id)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return strtr($format, [
            '{acronym}' => strtoupper($college?->acronym ?? 'COL'),
            '{year}'    => $year,
            '{type}'    => strtoupper($program->program_type ?? 'UG'),
            '{dept}'    => strtoupper($program->department?->acronym ?? 'DEPT'),
            '{program}' => strtoupper($program->acronym ?? 'PRG'),
            '{serial}'  => str_pad((string) $serial, 4, '0', STR_PAD_LEFT),
        ]);
    }
}
