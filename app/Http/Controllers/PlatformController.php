<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\College;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Platform super-admin. The super-admin has no college, so the CollegeScope is
 * a no-op and every query naturally spans all tenants. This panel gives a
 * cross-college overview, live analytics and one-click college registration.
 */
class PlatformController extends Controller
{
    public function dashboard(Request $request)
    {
        return view('platform.dashboard', $this->stats());
    }

    /** JSON for the dashboard's live auto-refresh. */
    public function liveStats()
    {
        $s = $this->stats();
        return response()->json([
            'colleges' => $s['totalColleges'],
            'students' => $s['totalStudents'],
            'staff'    => $s['totalStaff'],
            'applicants' => $s['totalApplicants'],
            'revenue'  => $s['totalRevenue'],
        ]);
    }

    private function stats(): array
    {
        $colleges = College::orderBy('name')->get();

        // Per-college aggregates (single grouped query each — unscoped for super-admin).
        $studentsByCollege  = Student::selectRaw('college_id, COUNT(*) c')->groupBy('college_id')->pluck('c', 'college_id');
        $staffByCollege     = User::whereNotIn('role', ['student', 'applicant', 'superadmin'])
            ->selectRaw('college_id, COUNT(*) c')->groupBy('college_id')->pluck('c', 'college_id');
        $revenueByCollege   = Invoice::where('status', 'paid')
            ->selectRaw('college_id, SUM(amount) s')->groupBy('college_id')->pluck('s', 'college_id');

        $rows = $colleges->map(fn ($c) => [
            'college'  => $c,
            'students' => (int) ($studentsByCollege[$c->id] ?? 0),
            'staff'    => (int) ($staffByCollege[$c->id] ?? 0),
            'revenue'  => (float) ($revenueByCollege[$c->id] ?? 0),
        ]);

        return [
            'colleges'        => $colleges,
            'rows'            => $rows,
            'totalColleges'   => $colleges->count(),
            'activeColleges'  => $colleges->where('is_active', true)->count(),
            'totalStudents'   => (int) Student::count(),
            'totalStaff'      => (int) User::whereNotIn('role', ['student', 'applicant', 'superadmin'])->count(),
            'totalApplicants' => (int) Applicant::count(),
            'totalRevenue'    => (float) Invoice::where('status', 'paid')->sum('amount'),
            'recentColleges'  => $colleges->sortByDesc('created_at')->take(5)->values(),
        ];
    }

    public function colleges()
    {
        return view('platform.colleges', $this->stats());
    }

    public function create()
    {
        return view('platform.register');
    }

    /** Leadership accounts the super-admin creates per college. */
    public const ADMIN_ROLES = ['proprietor', 'provost', 'registrar', 'bursar', 'mis', 'academic_secretary'];

    /** Register a new college (tenant). Admin accounts are added afterwards. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'acronym'          => 'required|string|max:20',
            'domain'           => 'nullable|string|max:255|unique:colleges,domain',
            'email'            => 'nullable|email|max:255',
            'phone'            => 'nullable|string|max:50',
            'address'          => 'nullable|string|max:255',
            'primary_color'    => 'nullable|string|max:20',
            'tagline'          => 'nullable|string|max:255',
            'established_year' => 'nullable|integer|min:1900|max:'.date('Y'),
        ]);

        $college = College::create([
            'name'    => $data['name'],
            'acronym' => strtoupper($data['acronym']),
            'domain'  => $data['domain'] ?? null,
            'email'   => $data['email'] ?? null,
            'phone'   => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'primary_color' => $data['primary_color'] ?? '#1d4ed8',
            'tagline' => $data['tagline'] ?? null,
            'established_year' => $data['established_year'] ?? null,
            'registration_no_format' => '{acronym}/{year}/{program}/{serial}',
            'is_active' => true,
        ]);

        return redirect()->route('platform.colleges.show', $college)
            ->with('success', "“{$college->name}” registered. Now create its leadership accounts below.");
    }

    public function show(College $college)
    {
        $students   = Student::where('college_id', $college->id)->count();
        $staff      = User::where('college_id', $college->id)->whereNotIn('role', ['student', 'applicant'])->count();
        $applicants = Applicant::where('college_id', $college->id)->count();
        $paid       = Invoice::where('college_id', $college->id)->where('status', 'paid');
        $revenue    = (clone $paid)->sum('amount');
        $admins     = User::where('college_id', $college->id)->whereIn('role', self::ADMIN_ROLES)->orderBy('role')->get();

        return view('platform.show', [
            'college' => $college, 'students' => $students, 'staff' => $staff,
            'applicants' => $applicants, 'revenue' => $revenue, 'admins' => $admins,
            'adminRoles' => self::ADMIN_ROLES,
            // Payments & settlement
            'banks'            => app(\App\Services\PaystackService::class)->banks(),
            'commissionEarned' => (float) (clone $paid)->sum('platform_commission'),
            'institutionShare' => (float) (clone $paid)->sum('institution_share'),
            'lastSettlement'   => (clone $paid)->whereNotNull('settlement_at')->max('settlement_at'),
            'recentTx'         => (clone $paid)->latest('paid_at')->take(8)->get(),
        ]);
    }

    /**
     * Onboard / update a college's Paystack subaccount + settlement details.
     * Idempotent: rerunning updates the existing subaccount in place.
     */
    public function updateSettlement(Request $request, College $college)
    {
        $data = $request->validate([
            'settlement_bank'           => 'required|string|max:20',   // Paystack bank code
            'settlement_account_number' => 'required|string|max:20',
            'commission_percentage'     => 'required|numeric|min:0|max:100',
            'settlement_account_name'   => 'nullable|string|max:255',
        ]);

        $paystack = app(\App\Services\PaystackService::class);

        // Best-effort account validation (resolves the real account name).
        $resolved = null;
        try {
            $resolved = $paystack->resolveBankAccount($data['settlement_bank'], $data['settlement_account_number']);
        } catch (\Throwable $e) {
            \Log::warning('Paystack resolve account failed', ['college' => $college->id, 'error' => $e->getMessage()]);
        }
        if (!$resolved && empty($data['settlement_account_name'])) {
            return back()->withInput()->with('error', 'Could not verify that settlement account. Check the bank and account number, or enter the account name manually.');
        }

        $college->fill([
            'settlement_bank'           => $data['settlement_bank'],
            'settlement_account_number' => $data['settlement_account_number'],
            'settlement_account_name'   => $resolved ?? $data['settlement_account_name'],
            'commission_percentage'     => $data['commission_percentage'],
        ])->save();

        try {
            $paystack->createOrUpdateSubaccount($college, [
                'settlement_bank'   => $data['settlement_bank'],
                'account_number'    => $data['settlement_account_number'],
                'percentage_charge' => $data['commission_percentage'],
                'business_name'     => $college->name,
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Settlement details saved, but the Paystack subaccount could not be created/updated: '.$e->getMessage());
        }

        return back()->with('success', "Subaccount configured for {$college->name} (code {$college->paystack_subaccount_code}, commission {$college->commission_percentage}%).");
    }

    /** Pull the latest subaccount status/details from Paystack. */
    public function syncSubaccount(College $college)
    {
        try {
            $data = app(\App\Services\PaystackService::class)->fetchSubaccount($college);
            return back()->with($data ? 'success' : 'error',
                $data ? 'Subaccount synced from Paystack.' : 'No subaccount to sync yet — create one first.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Subaccount sync failed: '.$e->getMessage());
        }
    }

    /** Per-college transactions + commission earnings (reconciliation view). */
    public function transactions(College $college)
    {
        $base = Invoice::where('college_id', $college->id);
        $invoices = (clone $base)->whereIn('status', ['paid', 'pending'])
            ->with('student')->latest('created_at')->paginate(50);

        $paid = (clone $base)->where('status', 'paid');
        $summary = [
            'count'      => (clone $paid)->count(),
            'gross'      => (float) (clone $paid)->sum('amount'),
            'commission' => (float) (clone $paid)->sum('platform_commission'),
            'share'      => (float) (clone $paid)->sum('institution_share'),
            'settled'    => (clone $paid)->where('settlement_status', 'settled')->count(),
        ];

        return view('platform.transactions', compact('college', 'invoices', 'summary'));
    }

    /** Edit a college's branding / domain. */
    public function update(Request $request, College $college)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'acronym'       => 'required|string|max:20',
            'domain'        => 'nullable|string|max:255|unique:colleges,domain,'.$college->id,
            'email'         => 'nullable|email|max:255',
            'phone'         => 'nullable|string|max:50',
            'address'       => 'nullable|string|max:255',
            'primary_color' => 'nullable|string|max:20',
            'tagline'       => 'nullable|string|max:255',
            'motto'         => 'nullable|string|max:255',
            'about'         => 'nullable|string|max:2000',
            'established_year' => 'nullable|integer|min:1900|max:'.date('Y'),
            'provost_name'  => 'nullable|string|max:255',
            'provost_message' => 'nullable|string|max:2000',
        ]);
        $data['acronym'] = strtoupper($data['acronym']);
        $college->update($data);

        return back()->with('success', 'College details updated.');
    }

    /** Create a leadership account for a college. */
    public function addAdmin(Request $request, College $college)
    {
        $data = $request->validate([
            'role'     => ['required', 'in:'.implode(',', self::ADMIN_ROLES)],
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        User::create([
            'name'                 => $data['name'],
            'email'                => $data['email'],
            'password'             => Hash::make($data['password']),
            'role'                 => $data['role'],
            'college_id'           => $college->id,
            'platform_fee_paid'    => true,
            'must_change_password' => true,
        ]);

        return back()->with('success', ucfirst(str_replace('_', ' ', $data['role']))." account created for {$college->name}.");
    }

    public function removeAdmin(College $college, User $user)
    {
        abort_unless($user->college_id === $college->id && in_array($user->role, self::ADMIN_ROLES, true), 403);
        $user->delete();
        return back()->with('success', 'Admin account removed.');
    }

    /** Reset a college admin's password and force a change on next login. */
    public function resetAdmin(Request $request, College $college, User $user)
    {
        abort_unless($user->college_id === $college->id && in_array($user->role, self::ADMIN_ROLES, true), 403);

        $temp = $request->input('password') ?: \Illuminate\Support\Str::password(10, true, true, false);
        $user->update(['password' => Hash::make($temp), 'must_change_password' => true]);

        return back()->with('success', "Password reset for {$user->email}. Temporary password: {$temp} (they will be prompted to change it).");
    }

    public function toggle(College $college)
    {
        $college->update(['is_active' => !$college->is_active]);
        return back()->with('success', $college->name.' is now '.($college->is_active ? 'active' : 'suspended').'.');
    }

    /** Permanently delete a college and ALL of its data. */
    public function destroy(College $college)
    {
        $id = $college->id;
        foreach ([
            \App\Models\StudentDocument::class, \App\Models\Invoice::class, \App\Models\FeeOrder::class,
            \App\Models\Applicant::class, \App\Models\Student::class, \App\Models\Subject::class,
            \App\Models\Program::class, \App\Models\Department::class, \App\Models\GradingScheme::class,
            User::class,
        ] as $model) {
            $model::withoutGlobalScopes()->where('college_id', $id)->delete();
        }
        $college->delete();

        return redirect()->route('platform.colleges')->with('success', 'College and all its data were permanently deleted.');
    }
}
