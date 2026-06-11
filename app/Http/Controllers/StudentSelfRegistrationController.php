<?php

namespace App\Http\Controllers;

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
        $college = College::where('is_active', true)->orderBy('id')->first();
        $departments = $college
            ? Department::withoutGlobalScopes()->where('college_id', $college->id)->orderBy('name')->get()
            : collect();
        $programs = $college
            ? Program::withoutGlobalScopes()->where('college_id', $college->id)->orderBy('name')->get()
            : collect();

        return view('auth.student_register', compact('college', 'departments', 'programs'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'surname'             => 'required|string|max:100',
            'first_name'          => 'required|string|max:100',
            'other_name'          => 'nullable|string|max:100',
            'address'             => 'required|string|max:255',
            'phone'               => 'required|string|max:50',
            'email'               => 'required|email|max:255|unique:users,email',
            'registration_number' => 'required|string|max:100|unique:students,registration_number',
            'program_id'          => 'required|exists:programs,id',
            'level'               => 'required|string|max:20',
            'password'            => 'required|string|min:6|confirmed',
            'passport'            => 'required|file|mimes:jpg,jpeg,png|max:2048',
        ]);

        $program = Program::withoutGlobalScopes()->with('department')->findOrFail($data['program_id']);
        $collegeId = $program->college_id;

        $username = \App\Support\Usernames::generate($data['first_name'], $data['other_name'] ?? null, $data['surname']);
        $fullName = trim($data['surname'].' '.$data['first_name'].' '.($data['other_name'] ?? ''));

        $pp = $request->file('passport');
        $passport = 'data:'.$pp->getMimeType().';base64,'.base64_encode(file_get_contents($pp->getRealPath()));

        // Account is created now but gated until the platform fee is paid.
        $user = User::create([
            'name'              => $fullName,
            'first_name'        => $data['first_name'],
            'surname'           => $data['surname'],
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
        ]);

        $student = Student::create([
            'full_name'           => $fullName,
            'email'               => $data['email'],
            'registration_number' => $data['registration_number'],
            'admission_number'    => $data['registration_number'],
            'college_id'          => $collegeId,
            'department_id'       => $program->department_id,
            'program_id'          => $program->id,
            'level'               => $data['level'],
            'class_arm'           => $program->name,
            'parent_phone'        => $data['phone'],
            'fees_balance'        => 0,
            'photo'               => $passport,
            'registration_status' => 'registration_paid', // can do registration once onboarded
        ]);

        $base = (float) config('services.paystack.platform_registration_fee', 5000);
        $invoice = Invoice::create([
            'college_id'  => $collegeId,
            'student_id'  => $student->id,
            'user_id'     => $user->id,
            'purpose'     => 'platform_registration',
            'description' => 'Platform registration fee',
            'amount'      => PaystackService::grossUpForFees($base), // payer bears the gateway fee
            'payer_email' => $data['email'],
            'status'      => 'pending',
            'reference'   => PaystackService::reference('PLT'),
            'meta'        => ['base_fee' => $base],
        ]);

        return redirect()->route('payments.initialize', $invoice);
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

        return redirect()->route('payments.initialize', $invoice);
    }

}
