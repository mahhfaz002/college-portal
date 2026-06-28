<?php

namespace App\Console\Commands;

use App\Models\Applicant;
use App\Models\ChangeOfCourseRequest;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Automatic retention cleanup. The portal must not keep temporary / incomplete
 * data forever — applications that were initiated but never paid for, abandoned
 * change-of-course requests, and cancelled invoices. Anything that is still
 * "temporary" past the retention window (default 48h) is cleared, freeing the
 * email + phone so the person can simply start again.
 *
 * Scheduled hourly (routes/console.php). Safe by construction:
 *   - NEVER touches a paid invoice, a real Student, staff, or the superadmin.
 *   - Only removes records that are unpaid/cancelled AND older than the window.
 *
 * Retention window: setting('temp_data_retention_hours', 48), overridable with
 * --hours. Use --dry-run to preview without deleting.
 */
class PruneTemporaryData extends Command
{
    protected $signature = 'data:prune-temporary
        {--hours= : Override the retention window in hours (default: setting or 48)}
        {--dry-run : Report what would be removed without deleting}';

    protected $description = 'Clear temporary/incomplete data (unpaid applications, abandoned requests, cancelled invoices) past the 48h retention window';

    public function handle(): int
    {
        $hours = (int) ($this->option('hours') ?: setting('temp_data_retention_hours', 48));
        $hours = max(1, $hours);
        $cutoff = now()->subHours($hours);
        $dry = (bool) $this->option('dry-run');

        // Real students' emails are the untouchable allow-list.
        $studentEmails = Student::withoutGlobalScopes()->whereNotNull('email')
            ->pluck('email')->map(fn ($e) => strtolower(trim($e)))->unique();

        // 1) Applications initiated but never paid for, older than the window.
        $staleApplicants = Applicant::withoutGlobalScopes()
            ->where('created_at', '<', $cutoff)
            ->where(fn ($q) => $q->where('payment_status', '!=', 'paid')->orWhereNull('payment_status'))
            ->get()
            ->filter(function ($a) use ($studentEmails) {
                if ($studentEmails->contains(strtolower(trim((string) $a->email)))) {
                    return false; // became a student — keep
                }
                // Keep anyone who actually has a settled invoice on record.
                return ! Invoice::withoutGlobalScopes()
                    ->where('applicant_id', $a->id)->where('status', 'paid')->exists();
            });

        // 2) Change-of-course requests that were never paid for.
        $staleCoc = Schema::hasTable('change_of_course_requests')
            ? ChangeOfCourseRequest::withoutGlobalScopes()
                ->where('status', 'pending_payment')->where('created_at', '<', $cutoff)->get()
            : collect();

        // 3) Cancelled invoices past the window (already dead — never reinstated).
        $cancelledInvoices = Invoice::withoutGlobalScopes()
            ->where('status', 'cancelled')->where('updated_at', '<', $cutoff);

        $applicantIds = $staleApplicants->pluck('id');
        $applicantEmails = $staleApplicants->pluck('email')->filter()->map(fn ($e) => strtolower(trim($e)))->unique();

        // Applicant-role logins tied to a pruned applicant (never a student email).
        $staleUsers = User::withoutGlobalScopes()->where('role', 'applicant')->get()
            ->filter(fn ($u) => ! $studentEmails->contains(strtolower(trim((string) $u->email)))
                && $applicantEmails->contains(strtolower(trim((string) $u->email))));

        // Non-paid invoices belonging to the pruned applicants / COC requests.
        $cocInvoiceIds = $staleCoc->pluck('invoice_id')->filter();
        $linkedInvoices = Invoice::withoutGlobalScopes()
            ->where('status', '!=', 'paid')
            ->where(function ($q) use ($applicantIds, $cocInvoiceIds) {
                $q->whereIn('applicant_id', $applicantIds->all() ?: [-1])
                  ->orWhereIn('id', $cocInvoiceIds->all() ?: [-1]);
            });

        // ---- Report ----
        $this->info('Prune temporary data — '.($dry ? 'DRY RUN' : 'LIVE').' (retention '.$hours.'h, cutoff '.$cutoff->toDateTimeString().')');
        $this->line('  Unpaid applications      : '.$staleApplicants->count());
        $this->line('  Applicant logins         : '.$staleUsers->count());
        $this->line('  Abandoned COC requests   : '.$staleCoc->count());
        $this->line('  Cancelled invoices       : '.(clone $cancelledInvoices)->count());
        $this->line('  Linked unpaid invoices   : '.(clone $linkedInvoices)->count());

        if ($dry) {
            $this->warn('Dry run — nothing deleted.');
            return self::SUCCESS;
        }

        $removed = DB::transaction(function () use (
            $staleApplicants, $applicantIds, $staleUsers, $staleCoc, $cancelledInvoices, $linkedInvoices
        ) {
            // Documents attached to the pruned applicants.
            if (Schema::hasTable('student_documents') && $applicantIds->isNotEmpty()) {
                \App\Models\StudentDocument::withoutGlobalScopes()
                    ->whereIn('applicant_id', $applicantIds)->delete();
            }

            $coc = $staleCoc->count();
            foreach ($staleCoc as $c) {
                $c->delete();
            }

            $inv = (clone $linkedInvoices)->count() + (clone $cancelledInvoices)->count();
            $linkedInvoices->delete();
            $cancelledInvoices->delete();

            $users = $staleUsers->count();
            User::withoutGlobalScopes()->whereIn('id', $staleUsers->pluck('id'))->where('role', 'applicant')->delete();

            $apps = $staleApplicants->count();
            Applicant::withoutGlobalScopes()->whereIn('id', $applicantIds)->delete();

            return compact('apps', 'users', 'coc', 'inv');
        });

        $summary = "Pruned {$removed['apps']} applications, {$removed['users']} logins, {$removed['coc']} COC requests, {$removed['inv']} invoices (older than {$hours}h).";
        $this->info($summary);
        \Log::info('data:prune-temporary — '.$summary);

        return self::SUCCESS;
    }
}
