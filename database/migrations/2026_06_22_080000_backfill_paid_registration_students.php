<?php

use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\Student;
use App\Services\RegistrationPromotionService;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill the students stranded by the registration-callback 500: every
 * applicant who PAID the registration fee but was never turned into a full
 * student gets their Student profile (registration number) and a promoted
 * "student" account. Runs after the students.photo widening migration so the
 * base64 passport no longer overflows. Per-applicant try/catch so one bad row
 * can never abort the deploy. Idempotent — the service skips anyone already
 * promoted. (Re-run on demand: php artisan students:promote-paid-registrations)
 */
return new class extends Migration
{
    public function up(): void
    {
        $promoter = app(RegistrationPromotionService::class);

        $applicantIds = Invoice::withoutGlobalScopes()
            ->where('purpose', 'registration_fee')->where('status', 'paid')
            ->whereNotNull('applicant_id')->distinct()->pluck('applicant_id');

        $applicants = Applicant::withoutGlobalScopes()->whereIn('id', $applicantIds)->get();

        foreach ($applicants as $applicant) {
            if (! $applicant->admitted_program_id) {
                continue;
            }
            $alreadyFull = Student::withoutGlobalScopes()->where('email', $applicant->email)->exists()
                && $applicant->application_status === 'registered';
            if ($alreadyFull) {
                continue;
            }

            try {
                $promoter->promote($applicant);
            } catch (\Throwable $e) {
                \Log::warning('Backfill promote failed', ['applicant' => $applicant->id, 'error' => $e->getMessage()]);
            }
        }
    }

    public function down(): void
    {
        // No-op: we never want to demote students back to applicants.
    }
};
