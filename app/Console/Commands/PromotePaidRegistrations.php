<?php

namespace App\Console\Commands;

use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\Student;
use App\Services\RegistrationPromotionService;
use Illuminate\Console\Command;

/**
 * Backfill: promote every applicant who has PAID the registration fee but was
 * never turned into a full student — the cohort stranded by the registration
 * callback 500 (students.photo overflow). Idempotent; safe to re-run.
 *
 *   php artisan students:promote-paid-registrations
 *   php artisan students:promote-paid-registrations --dry-run
 */
class PromotePaidRegistrations extends Command
{
    protected $signature = 'students:promote-paid-registrations {--dry-run : List who would be promoted without changing anything}';
    protected $description = 'Create the student profile (reg number + role) for applicants who paid the registration fee.';

    public function handle(RegistrationPromotionService $promoter): int
    {
        $applicantIds = Invoice::withoutGlobalScopes()
            ->where('purpose', 'registration_fee')->where('status', 'paid')
            ->whereNotNull('applicant_id')->distinct()->pluck('applicant_id');

        $applicants = Applicant::withoutGlobalScopes()->whereIn('id', $applicantIds)->get();

        if ($applicants->isEmpty()) {
            $this->info('No registration-fee-paid applicants found.');
            return self::SUCCESS;
        }

        $promoted = 0; $already = 0; $skipped = 0; $failed = 0;

        foreach ($applicants as $applicant) {
            $isFull = Student::withoutGlobalScopes()->where('email', $applicant->email)->exists()
                && $applicant->application_status === 'registered';

            if ($isFull) {
                $already++;
                continue;
            }

            if (! $applicant->admitted_program_id) {
                $skipped++;
                $this->warn("• {$applicant->full_name} ({$applicant->email}): no admitted programme — skipped.");
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("• WOULD promote: {$applicant->full_name} ({$applicant->email})");
                $promoted++;
                continue;
            }

            try {
                $student = $promoter->promote($applicant);
                if ($student) {
                    $promoted++;
                    $this->info("✓ {$applicant->full_name} → {$student->registration_number}");
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error("✗ {$applicant->full_name} ({$applicant->email}): {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info(($this->option('dry-run') ? '[dry-run] ' : '')
            ."Done — {$promoted} promoted, {$already} already full students, {$skipped} skipped, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
