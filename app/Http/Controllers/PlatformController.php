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

    /** Register a new college (tenant) + its first administrator accounts. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'acronym'        => 'required|string|max:20',
            'domain'         => 'nullable|string|max:255|unique:colleges,domain',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:50',
            'address'        => 'nullable|string|max:255',
            'primary_color'  => 'nullable|string|max:20',
            'tagline'        => 'nullable|string|max:255',
            // First administrator
            'admin_name'     => 'required|string|max:255',
            'admin_email'    => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:6',
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
            'registration_no_format' => '{acronym}/{year}/{type}/{program}/{serial}',
            'is_active' => true,
        ]);

        // First Registrar (primary admin) + an MIS account so the college can
        // immediately build its academic structure and register staff.
        User::create([
            'name'                 => $data['admin_name'],
            'email'                => $data['admin_email'],
            'password'             => Hash::make($data['admin_password']),
            'role'                 => 'registrar',
            'college_id'           => $college->id,
            'platform_fee_paid'    => true,
            'must_change_password' => true,
        ]);

        return redirect()->route('platform.colleges')
            ->with('success', "“{$college->name}” registered. Registrar login: {$data['admin_email']} (temporary password set; they will be prompted to change it).");
    }

    public function show(College $college)
    {
        $students = Student::where('college_id', $college->id)->count();
        $staff    = User::where('college_id', $college->id)->whereNotIn('role', ['student', 'applicant'])->count();
        $applicants = Applicant::where('college_id', $college->id)->count();
        $revenue  = Invoice::where('college_id', $college->id)->where('status', 'paid')->sum('amount');
        $admins   = User::where('college_id', $college->id)->whereIn('role', ['registrar', 'mis', 'proprietor'])->get();

        return view('platform.show', compact('college', 'students', 'staff', 'applicants', 'revenue', 'admins'));
    }

    public function toggle(College $college)
    {
        $college->update(['is_active' => !$college->is_active]);
        return back()->with('success', $college->name.' is now '.($college->is_active ? 'active' : 'suspended').'.');
    }
}
