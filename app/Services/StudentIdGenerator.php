<?php

namespace App\Services;

use App\Models\College;
use App\Models\Program;
use App\Models\Student;

/**
 * Builds a student/registration number following the per-college rule, e.g.
 *   MAHHFAZ/2026/SLT/ND/0001
 * = collegeAcronym / admissionYear / departmentAcronym / programAcronym / serial
 *
 * The serial is per (program, year). Wired into the registration workflow in
 * Phase 3; provided now so the rule lives in one place.
 */
class StudentIdGenerator
{
    public function generate(Program $program, ?int $year = null): string
    {
        $year ??= (int) date('Y');
        $college = current_college() ?? College::find($program->college_id);
        $program->loadMissing('department');

        $format = $college?->registration_no_format ?: '{acronym}/{year}/{dept}/{program}/{serial}';

        $serial = Student::where('program_id', $program->id)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return strtr($format, [
            '{acronym}' => strtoupper($college?->acronym ?? 'COL'),
            '{year}'    => $year,
            '{dept}'    => strtoupper($program->department?->acronym ?? 'DEPT'),
            '{program}' => strtoupper($program->acronym ?? 'PRG'),
            '{serial}'  => str_pad((string) $serial, 4, '0', STR_PAD_LEFT),
        ]);
    }
}
