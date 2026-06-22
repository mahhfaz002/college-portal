<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;

/**
 * Promotes a registration-fee-paid applicant into a full student: creates the
 * Student (with a registration number), marks the applicant registered and
 * flips the account role to "student".
 *
 * Idempotent — safe to re-run. Used by the live payment fulfilment AND by the
 * one-off backfill for applicants stranded by the registration-callback 500.
 */
class RegistrationPromotionService
{
    public function __construct(private StudentIdGenerator $ids) {}

    /**
     * Complete registration for an applicant. Returns the Student, or null when
     * the applicant has no admitted programme to register against.
     */
    public function promote(Applicant $applicant): ?Student
    {
        $program = Program::withoutGlobalScopes()->with('department')->find($applicant->admitted_program_id);
        if (!$program) {
            return null;
        }

        $student = Student::withoutGlobalScopes()->where('email', $applicant->email)->first();

        if (!$student) {
            $regNumber = $this->ids->generate($program);

            $student = Student::create([
                'full_name'           => $applicant->full_name ?: trim($applicant->surname.' '.$applicant->first_name),
                'email'               => $applicant->email,
                'admission_number'    => $applicant->admission_number ?: $regNumber, // NOT NULL — fall back to the reg no.
                'registration_number' => $regNumber,
                'college_id'          => $applicant->college_id,
                'department_id'       => $program->department_id,
                'program_id'          => $program->id,
                'level'               => '100',
                'class_arm'           => $program->name,
                'parent_phone'        => $applicant->guardian_phone ?? $applicant->parent_phone,
                'fees_balance'        => 0,
                'photo'               => $applicant->passport,
                'applicant_id'        => $applicant->id,
                'registration_status' => 'registration_paid',
            ]);
        }

        if ($applicant->application_status !== 'registered') {
            $applicant->update(['application_status' => 'registered']);
        }

        if ($applicant->user_id) {
            User::withoutGlobalScopes()->where('id', $applicant->user_id)
                ->where('role', '!=', 'student')
                ->update(['role' => 'student']);
        }

        return $student;
    }
}
