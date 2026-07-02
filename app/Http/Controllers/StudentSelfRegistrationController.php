<?php

namespace App\Http\Controllers;

use App\Models\AdmittedRecord;
use App\Models\College;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Public student self-onboarding. Existing students create their own portal
 * account, then pay a one-off platform registration fee (to the platform owner)
 * before the account is usable.
 */
class StudentSelfRegistrationController extends Controller
{
    public function showForm()
    {
        // STRICT host-based tenancy: register against the college that owns THIS
        // domain (bound by SetCollegeContext) — never "the first college".
        $college = $this->resolveCollege();
        abort_unless($college, 404);

        // Step 1: the student must first identify themselves by registration
        // number (matched against the admitted-records the college uploaded).
        return view('auth.student_register', ['college' => $college, 'record' => null]);
    }

    /**
     * Step 2 — match the registration number to an admitted record and, if found,
     * render the prefilled account form. Otherwise tell them to see the registrar.
     */
    public function lookup(Request $request)
    {
        $college = $this->resolveCollege();
        abort_unless($college, 404);

        $data = $request->validate(['registration_number' => 'required|string|max:100']);
        $record = $this->findRecord($college, $data['registration_number']);

        if (!$record) {
            return back()->withInput()->withErrors([
                'registration_number' => 'No admitted record was found for that registration number. Please contact the Registrar.',
            ]);
        }
        if ($record->isClaimed()) {
            return back()->withErrors([
                'registration_number' => 'An account has already been created for this registration number — please log in instead.',
            ]);
        }

        $programs = Program::withoutGlobalScopes()->with('department')
            ->where('college_id', $college->id)->orderBy('name')->get();

        return view('auth.student_register', compact('college', 'record', 'programs'));
    }

    public function store(Request $request)
    {
        $college = $this->resolveCollege();
        abort_unless($college, 404);

        $data = $request->validate([
            'registration_number' => 'required|string|max:100',
            'program_id'          => 'required|exists:programs,id',
            'address'             => 'required|string|max:255',
            'phone'               => 'required|string|max:50',
            'email'               => 'required|email|max:255|unique:users,email',
            'password'            => 'required|string|min:8|confirmed',
            'passport'            => 'required|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        // GATE: the registration number MUST match an unclaimed admitted record
        // for this college. This is the server-side identity check — the prefilled
        // fields are never trusted.
        $record = $this->findRecord($college, $data['registration_number']);
        if (!$record || $record->isClaimed()) {
            return redirect()->route('student.register')->withErrors([
                'registration_number' => 'That registration number is not eligible for an account. Please contact the Registrar.',
            ]);
        }
        if (Student::withoutGlobalScopes()->where('registration_number', $record->registration_number)->exists()) {
            return redirect()->route('student.register')->withErrors([
                'registration_number' => 'An account already exists for this registration number.',
            ]);
        }

        $program = Program::withoutGlobalScopes()->with('department')->findOrFail($data['program_id']);
        if ((int) $program->college_id !== (int) $college->id) {
            abort(403, 'This programme does not belong to this institution.');
        }

        $collegeId = $college->id;
        $fullName  = $record->full_name;
        $parts     = preg_split('/\s+/', trim($fullName));
        $username  = \App\Support\Usernames::generate($parts[1] ?? ($parts[0] ?? 'student'), null, $parts[0] ?? 'student');

        $pp = $request->file('passport');
        $passport = 'data:'.$pp->getMimeType().';base64,'.base64_encode(file_get_contents($pp->getRealPath()));

        // Account is created now but gated until the platform fee is paid.
        $user = User::create([
            'name'              => $fullName,
            'email'             => $data['email'],
            'username'          => $username,
            'phone'             => $data['phone'],
            'password'          => Hash::make($data['password']),
            'role'              => 'student',
            'college_id'        => $collegeId,
            'department_id'     => $program->department_id,
            'program_id'        => $program->id,
            'passport'          => $passport,
            'platform_fee_paid' => false,
            'must_change_password' => false,
            'email_verified_at' => now(),   // identity confirmed via the admitted record + paid fee
        ]);

        $student = Student::create([
            'full_name'           => $fullName,
            'email'               => $data['email'],
            'registration_number' => $record->registration_number,
            'admission_number'    => $record->registration_number,
            'college_id'          => $collegeId,
            'department_id'       => $program->department_id,
            'program_id'          => $program->id,
            'level'               => $record->level ?: '100',
            'class_arm'           => $program->name,
            'parent_phone'        => $data['phone'],
            'fees_balance'        => 0,
            'photo'               => $passport,
            'registration_status' => 'registration_paid', // can do registration once onboarded
        ]);

        // Claim the admitted record so it can't be reused.
        $record->update(['claimed_at' => now(), 'claimed_by' => $user->id]);

        $base = (float) config('services.paystack.platform_registration_fee', 5000);
        $invoice = Invoice::create([
            'college_id'  => $collegeId,
            'student_id'  => $student->id,
            'user_id'     => $user->id,
            'purpose'     => 'platform_registration',
            'description' => 'Platform registration fee',
            'amount'      => $base, // surcharges (convenience + gateway fee) added at checkout
            'payer_email' => $data['email'],
            'status'      => 'pending',
            'reference'   => PaystackService::reference('PLT'),
            'meta'        => ['base_fee' => $base],
        ]);

        return redirect()->route('payments.checkout', $invoice);
    }

    /** Re-initialise the pending platform invoice for a gated student. */
    public function pay()
    {
        $invoice = Invoice::where('user_id', auth()->id())
            ->where('purpose', 'platform_registration')
            ->where('status', 'pending')
            ->latest()->first();

        if (!$invoice) {
            // Nothing pending — clear the gate.
            auth()->user()->update(['platform_fee_paid' => true]);
            return redirect()->route('dashboard');
        }

        return redirect()->route('payments.checkout', $invoice);
    }

    /** The college that owns this request (host-based; local dev falls back). */
    private function resolveCollege(): ?College
    {
        $college = current_college();
        if (!$college && app()->isLocal()) {
            $college = College::where('is_active', true)->orderBy('id')->first();
        }
        return $college;
    }

    /** An admitted record for this college, matched case-insensitively by reg no. */
    private function findRecord(College $college, string $reg): ?AdmittedRecord
    {
        return AdmittedRecord::withoutGlobalScopes()
            ->where('college_id', $college->id)
            ->whereRaw('LOWER(registration_number) = ?', [strtolower(trim($reg))])
            ->first();
    }
}
