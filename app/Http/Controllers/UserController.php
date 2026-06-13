<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Support\Sections;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Operational staff the Registrar may create. The college's leadership
     * accounts (proprietor, provost, registrar, bursar, mis, academic_secretary)
     * are created by the platform super-admin, not here.
     */
    private const STAFF_ROLES = [
        'lecturer', 'exam_officer', 'admission_officer',
        'hod', 'assistant_hod', 'student_affairs',
        'librarian', 'office_secretary',
    ];

    /**
     * List all staff with their assignments.
     */
    public function index()
    {
        $staff = User::whereNotIn('role', ['student', 'applicant', 'superadmin'])
            ->with(['departmentModel'])
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return view('staff.index', [
            'staff'       => $staff,
            'departments' => \App\Models\Department::orderBy('name')->get(['id', 'name', 'section']),
            'sections'    => array_merge(\App\Support\Sections::ALL, ['Others']),
        ]);
    }

    /**
     * Show the staff registration form.
     */
    public function create()
    {
        return view('staff.create', [
            'classes'  => SchoolClass::orderBy('section')->orderBy('name')->get(),
            'subjects' => Subject::orderBy('section')->orderBy('name')->get(),
            'roles'    => self::STAFF_ROLES,
            'sections' => Sections::ALL,
            'departments' => \App\Models\Department::orderBy('name')->get(),
        ]);
    }

    /**
     * Register a new staff member: auto login email, auto staff ID,
     * base64 passport, plus multi-class/multi-subject assignment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'        => 'required|string|max:255',
            'surname'           => 'required|string|max:255',
            'role'              => ['required', Rule::in(self::STAFF_ROLES)],
            'phone'             => 'nullable|string|max:30',
            'email'             => 'nullable|email|max:255|unique:users,email',
            'department_id'     => 'nullable|exists:departments,id',
            'employed_year'     => 'nullable|string|max:9',
            'password'          => 'nullable|string|min:6',
            'passport'          => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $email = $validated['email'] ?? $this->generateEmail($validated['first_name'], $validated['surname']);
        $plainPassword = $validated['password'] ?? Str::random(8);

        $user = User::create([
            'first_name'        => $validated['first_name'],
            'surname'           => $validated['surname'],
            'name'              => trim($validated['first_name'].' '.$validated['surname']),
            'email'             => $email,
            'password'          => Hash::make($plainPassword),
            'role'              => $validated['role'],
            'phone'             => $validated['phone'] ?? null,
            'department_id'     => $validated['department_id'] ?? null,
            'department'        => optional(\App\Models\Department::find($validated['department_id'] ?? null))->name,
            'employed_year'     => $validated['employed_year'] ?? date('Y'),
            'passport'          => $this->encodePassport($request),
            'staff_id'          => $this->generateStaffId($validated['role'], $validated['employed_year'] ?? date('Y'), optional(\App\Models\Department::find($validated['department_id'] ?? null))->acronym),
            'status'            => 'active',
            'must_change_password' => true,
        ]);

        return redirect()->route('staff.show', $user)->with('success',
            "Staff account created. Login email: {$email} | Temp password: {$plainPassword} | Staff ID: {$user->staff_id}");
    }

    /**
     * Staff detail page — all info + assigned classes/subjects.
     */
    public function show(User $user)
    {
        $user->load(['classes', 'subjects']);

        return view('staff.show', [
            'staff'       => $user,
            'allClasses'  => SchoolClass::orderBy('section')->orderBy('name')->get(),
            'allSubjects' => Subject::orderBy('section')->orderBy('name')->get(),
            'sections'    => Sections::ALL,
        ]);
    }

    /**
     * Edit form for a staff member's core info.
     */
    public function edit(User $user)
    {
        return view('staff.edit', [
            'staff' => $user,
            'roles' => self::STAFF_ROLES,
            'departments' => \App\Models\Department::orderBy('name')->get(),
        ]);
    }

    /**
     * Update a staff member's information.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name'        => 'required|string|max:255',
            'surname'           => 'required|string|max:255',
            'role'              => ['required', Rule::in(self::STAFF_ROLES)],
            'email'             => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'             => 'nullable|string|max:30',
            'department_id'     => 'nullable|exists:departments,id',
            'employed_year'     => 'nullable|string|max:9',
            'next_of_kin_name'  => 'nullable|string|max:255',
            'next_of_kin_phone' => 'nullable|string|max:30',
            'status'            => ['nullable', Rule::in(['active', 'inactive'])],
            'passport'          => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $validated['name'] = trim($validated['first_name'].' '.$validated['surname']);
        $validated['department'] = optional(\App\Models\Department::find($validated['department_id'] ?? null))->name;

        if ($request->hasFile('passport')) {
            $validated['passport'] = $this->encodePassport($request);
        } else {
            unset($validated['passport']);
        }

        $user->update($validated);

        return redirect()->route('staff.show', $user)->with('success', 'Staff information updated.');
    }

    /**
     * Add or remove class & subject assignments from the detail page.
     */
    public function updateAssignments(Request $request, User $user)
    {
        $validated = $request->validate([
            'class_ids'     => 'nullable|array',
            'class_ids.*'   => 'integer|exists:classes,id',
            'subject_ids'   => 'nullable|array',
            'subject_ids.*' => 'integer|exists:subjects,id',
        ]);

        $user->classes()->sync($validated['class_ids'] ?? []);
        $user->subjects()->sync($validated['subject_ids'] ?? []);

        // Keep legacy single-value columns roughly in sync (first assignment).
        $user->update([
            'class_assigned'   => optional($user->classes()->first())->name,
            'subject_assigned' => optional($user->subjects()->first())->name,
        ]);

        return redirect()->route('staff.show', $user)->with('success', 'Assignments updated.');
    }

    /**
     * Delete a staff member.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('staff.index')->with('success', "{$name}'s account has been removed.");
    }

    /**
     * Render the staff ID card.
     */
    public function idCard(User $user)
    {
        $user->load('classes');
        return view('staff.id_card', ['staff' => $user]);
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function syncAssignments(User $user, Request $request): void
    {
        $user->classes()->sync($request->input('class_ids', []));
        $user->subjects()->sync($request->input('subject_ids', []));

        $user->update([
            'class_assigned'   => optional($user->classes()->first())->name,
            'subject_assigned' => optional($user->subjects()->first())->name,
        ]);
    }

    /**
     * firstInitial + surname @ domain, with numeric suffix on collision.
     */
    private function generateEmail(string $firstName, string $surname): string
    {
        $domain = setting('staff_email_domain', 'school.test');
        $base = Str::lower(Str::substr($firstName, 0, 1).preg_replace('/\s+/', '', $surname));
        $base = preg_replace('/[^a-z0-9]/', '', $base) ?: 'staff';

        $email = "{$base}@{$domain}";
        $i = 1;
        while (User::where('email', $email)->exists()) {
            $email = "{$base}{$i}@{$domain}";
            $i++;
        }

        return $email;
    }

    /**
     * ACRONYM/YEAR[/DEPT]/SEQ — e.g. MAH/2026/TEA/004.
     */
    private function generateStaffId(string $role, string $year, ?string $department): string
    {
        // Use the registering college's own acronym/name (NOT a global setting),
        // so each tenant's staff IDs carry that college's identity.
        $college = current_college();
        $acronym = Str::upper(
            $college?->acronym
            ?: Str::substr($college?->name ?? setting('school_name', 'SCH'), 0, 3)
        );
        $dept = $department ? '/'.Str::upper(Str::substr($department, 0, 3)) : '';

        $n = User::whereNotNull('staff_id')->count() + 1;
        do {
            $seq = str_pad((string) $n, 3, '0', STR_PAD_LEFT);
            $staffId = "{$acronym}/{$year}{$dept}/{$seq}";
            $n++;
        } while (User::where('staff_id', $staffId)->exists());

        return $staffId;
    }

    private function encodePassport(Request $request): ?string
    {
        if (!$request->hasFile('passport')) {
            return null;
        }

        $file = $request->file('passport');
        return 'data:'.$file->getMimeType().';base64,'.base64_encode(file_get_contents($file->getRealPath()));
    }
}
