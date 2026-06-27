<?php

namespace App\Console\Commands;

use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Remove redundant prospective-applicant records that never became full
 * students — the leftover / test emails that accumulate before launch.
 *
 * SAFETY (this command can NEVER touch live people):
 *   - It only ever deletes Applicant rows and Users whose role is exactly
 *     'applicant'. Staff, superadmin and students are never in scope.
 *   - Any email that belongs to a real Student is preserved, even if an
 *     applicant row also exists for it (that's the student's original
 *     application).
 *   - By default, applicants who have PAID are preserved too (pass
 *     --include-paid to drop that protection).
 *   - It is DRY-RUN by default; nothing is deleted without --force.
 *   - Paid invoices are never deleted (financial audit trail stays intact);
 *     only the pending/cancelled invoices of pruned applicants are removed.
 *
 * Usage:
 *   php artisan applicants:prune-stale                 # dry run (shows what would go)
 *   php artisan applicants:prune-stale --force         # actually delete
 *   php artisan applicants:prune-stale --include-paid  # also drop paid-but-not-student applicants
 *   php artisan applicants:prune-stale --college=3     # scope to one college
 */
class PruneStaleApplicants extends Command
{
    protected $signature = 'applicants:prune-stale
        {--force : Actually delete (without this flag it only reports)}
        {--include-paid : Also prune applicants who paid but never became students}
        {--college= : Restrict to a single college id}';

    protected $description = 'Remove redundant prospective-applicant records/emails that never became full students';

    public function handle(): int
    {
        $force       = (bool) $this->option('force');
        $includePaid = (bool) $this->option('include-paid');
        $college     = $this->option('college');

        // Emails that belong to real students — the untouchable allow-list.
        $studentEmails = Student::withoutGlobalScopes()->whereNotNull('email')
            ->pluck('email')->map(fn ($e) => strtolower(trim($e)))->unique();

        // Stale applicants: not a student, and (unless --include-paid) not paid.
        $applicants = Applicant::withoutGlobalScopes()
            ->when($college, fn ($q) => $q->where('college_id', $college))
            ->get()
            ->filter(function ($a) use ($studentEmails, $includePaid) {
                if ($studentEmails->contains(strtolower(trim((string) $a->email)))) {
                    return false; // a real student — keep
                }
                if (! $includePaid && $a->payment_status === 'paid') {
                    return false; // paid applicant — keep unless explicitly included
                }
                return true;
            });

        $applicantIds    = $applicants->pluck('id');
        $applicantEmails = $applicants->pluck('email')->filter()->map(fn ($e) => strtolower(trim($e)))->unique();

        // Applicant-role users tied to a pruned applicant (never a student email).
        $users = User::withoutGlobalScopes()->where('role', 'applicant')->get()
            ->filter(function ($u) use ($studentEmails, $applicantEmails) {
                $email = strtolower(trim((string) $u->email));
                if ($studentEmails->contains($email)) {
                    return false; // hard guard — never delete a student's login
                }
                return $applicantEmails->contains($email);
            });

        $invoiceQuery = Invoice::withoutGlobalScopes()
            ->whereIn('applicant_id', $applicantIds->all() ?: [-1])
            ->where('status', '!=', 'paid'); // keep paid invoices for the record

        // ---- Report ----
        $this->newLine();
        $this->info('Prune stale applicants — '.($force ? 'LIVE RUN' : 'DRY RUN (nothing will be deleted)'));
        $this->line('  Mode            : '.($includePaid ? 'including paid applicants' : 'paid applicants preserved'));
        $this->line('  College scope   : '.($college ? "id={$college}" : 'all colleges'));
        $this->newLine();
        $this->line('  Would delete:');
        $this->line('    Applicant records : '.$applicants->count());
        $this->line('    Applicant logins  : '.$users->count());
        $this->line('    Unpaid invoices   : '.$invoiceQuery->count());
        $this->newLine();
        $this->line('  Preserved (never touched):');
        $this->line('    Full students     : '.Student::withoutGlobalScopes()->count());
        $this->line('    Staff + superadmin: '.User::withoutGlobalScopes()->where('role', '!=', 'applicant')->whereNotIn('role', ['student'])->count());
        $this->line('    Student logins     : '.User::withoutGlobalScopes()->where('role', 'student')->count());

        if ($applicants->isNotEmpty()) {
            $this->newLine();
            $this->line('  Sample of applicant emails to remove:');
            foreach ($applicants->take(15) as $a) {
                $this->line('    - '.$a->email.'  ('.($a->payment_status ?: 'unpaid').', '.($a->application_status ?: 'n/a').')');
            }
            if ($applicants->count() > 15) {
                $this->line('    … and '.($applicants->count() - 15).' more');
            }
        }

        if (! $force) {
            $this->newLine();
            $this->warn('Dry run only. Re-run with --force to delete the records above.');
            return self::SUCCESS;
        }

        if ($applicants->isEmpty() && $users->isEmpty()) {
            $this->newLine();
            $this->info('Nothing to prune. Database is already clean.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($invoiceQuery, $users, $applicantIds) {
            $invoiceQuery->delete();
            User::withoutGlobalScopes()->whereIn('id', $users->pluck('id'))->where('role', 'applicant')->delete();
            Applicant::withoutGlobalScopes()->whereIn('id', $applicantIds)->delete();
        });

        $this->newLine();
        $this->info('Done. Stale applicant records removed; students, staff and superadmin untouched.');
        return self::SUCCESS;
    }
}
