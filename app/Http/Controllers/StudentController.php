<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Score;
use App\Models\Remark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    /**
     * Display the list of all students with Search functionality.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $departments = \App\Models\Department::orderBy('name')->get();
        $programs    = \App\Models\Program::with('department')->orderBy('name')->get();

        // Section filter maps to the departments in that section.
        $sectionDeptIds = $request->filled('section')
            ? $departments->where('section', $request->section)->pluck('id')
            : null;

        $students = Student::with(['department', 'program'])
            ->when($search, fn ($q) => $q->where(fn ($w) => $w
                ->where('full_name', 'LIKE', "%{$search}%")
                ->orWhere('admission_number', 'LIKE', "%{$search}%")
                ->orWhere('registration_number', 'LIKE', "%{$search}%")))
            ->when($sectionDeptIds !== null, fn ($q) => $q->whereIn('department_id', $sectionDeptIds))
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('program_id'), fn ($q) => $q->where('program_id', $request->program_id))
            ->when($request->filled('level'), fn ($q) => $q->where('level', $request->level))
            ->orderBy('full_name')
            ->paginate(50)
            ->withQueryString();

        return view('students.index', [
            'students'      => $students,
            'departments'   => $departments,
            'programs'      => $programs,
            'sections'      => \App\Support\Sections::ALL,
            'sectionLabels' => \App\Support\Sections::LABELS,
        ]);
    }

    /**
     * Show the form for admitting a new student.
     */
    public function create()
    {
        return view('students.create');
    }

    /**
     * Save the new student data into the database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'admission_number' => 'required|string|unique:students',
            'class_arm' => 'required|string',
            'parent_phone' => 'required|string',
            'fees_balance' => 'required|numeric',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('photos');
            $validated['photo'] = $path;
        }

        Student::create($validated);

        return redirect()->route('students.index')->with('success', 'Student Admitted Successfully!');
    }

    /**
     * Show the Statement of Account (Payment History). Merges the legacy manual
     * `payments` table with settled online `invoices` (Paystack) so the full
     * payment history for the student shows — college-scoped automatically.
     */
    public function show(Student $student)
    {
        $manual = $student->payments()->latest()->get()->map(fn ($p) => [
            'date'        => $p->created_at,
            'method'      => $p->payment_method ?? 'manual',
            'description' => $p->description ?: 'Fees payment',
            'amount'      => (float) $p->amount,
            'receipt'     => route('payments.receipt', $p->id),
        ]);

        $online = \App\Models\Invoice::where('student_id', $student->id)
            ->where('status', 'paid')
            ->latest('paid_at')->get()->map(fn ($inv) => [
                'date'        => $inv->paid_at ?? $inv->updated_at,
                'method'      => 'paystack',
                'description' => $inv->description ?: ucfirst(str_replace('_', ' ', $inv->purpose ?? 'payment')),
                'amount'      => (float) $inv->amount,
                'receipt'     => route('invoices.receipt', $inv->id),
            ]);

        $payments = $manual->concat($online)
            ->sortByDesc('date')
            ->values();

        $totalPaid = $payments->sum('amount');

        return view('students.show', compact('student', 'payments', 'totalPaid'));
    }

    /**
     * Show the form for editing a specific student.
     */
    public function edit(Student $student)
    {
        return view('students.edit', compact('student'));
    }

    /**
     * Update the student's information in the database.
     */
    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'admission_number' => 'required|string|unique:students,admission_number,' . $student->id,
            'class_arm' => 'required|string',
            'parent_phone' => 'required|string',
            'fees_balance' => 'required|numeric',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            // Delete old photo if it exists to save space
            if ($student->photo) {
                Storage::disk('public')->delete($student->photo);
            }
            $validated['photo'] = $request->file('photo')->store('photos');
        }

        $student->update($validated);

        return redirect()->route('students.index')->with('success', 'Student Record Updated Successfully!');
    }

    /**
     * Remove the specified student from the database.
     */
    public function destroy(Student $student)
    {
        if ($student->photo) {
            Storage::disk('public')->delete($student->photo);
        }
        $student->delete();
        return redirect()->route('students.index')->with('success', 'Student Record Deleted Successfully!');
    }

    /**
     * Generate the Student Report Card with Ranking and Attendance.
     */
    public function reportCard(Student $student, Request $request)
    {
        // Oversight roles (proprietor / provost) may view the student LIST and
        // payment history only — never a student's academic end-of-term report.
        abort_if(auth()->user()->hasRole('proprietor', 'provost'), 403,
            'Oversight roles can view the student list and payment history only.');

        $term = $request->query('term', '1st Term');
        $session = $request->query('session', '2025/2026');

        $scores = $student->scores()
            ->where('term', $term)
            ->where('session', $session)
            ->with('subject')
            ->get();

        $remark = Remark::where('student_id', $student->id)
                    ->where('term', $term)
                    ->where('session', $session)
                    ->first();

        $classmatesIds = Student::where('class_arm', $student->class_arm)->pluck('id');
        $totalInClass = $classmatesIds->count();

        $rankings = Score::whereIn('student_id', $classmatesIds)
            ->where('term', $term)
            ->where('session', $session)
            ->select('student_id', DB::raw('SUM(ca_score + exam_score) as total_marks'))
            ->groupBy('student_id')
            ->orderBy('total_marks', 'desc')
            ->get();

        $position = $rankings->search(fn($item) => $item->student_id == $student->id);
        $position = ($position !== false) ? $position + 1 : null;

        return view('students.report-card', compact(
            'student', 'scores', 'term', 'session', 'position', 'remark', 'totalInClass'
        ));
    }

    /**
     * Save/Update Teacher Remarks.
     */
    public function saveRemark(Request $request, Student $student)
    {
        Remark::updateOrCreate(
            [
                'student_id' => $student->id,
                'term' => $request->input('term'),
                'session' => $request->input('session'),
            ],
            ['teacher_comment' => $request->input('teacher_comment')]
        );

        return redirect()->back()->with('success', 'Comment saved successfully!');
    }

    /**
     * Generate Student ID Card.
     */
    public function idCard(Student $student)
    {
        return view('students.id_card', compact('student'));
    }

    /**
     * Show Promotion Form.
     */
    public function promotionForm()
    {
        $classes = Student::distinct()->pluck('class_arm');
        return view('students.promotion', compact('classes'));
    }

    /**
     * Handle Bulk Class Promotion.
     */
    public function promote(Request $request)
    {
        $request->validate([
            'current_class' => 'required',
            'target_class' => 'required',
        ]);

        $count = Student::where('class_arm', $request->current_class)
            ->update(['class_arm' => $request->target_class]);

        return redirect()->back()->with('success', "Promoted $count students to {$request->target_class}!");
    }
    public function dashboard()
{
    $student = auth()->user();
    // Assuming you have a 'grades' relationship or table
    // $results = Grade::where('student_id', $student->id)->get();

    return view('student.dashboard', compact('student'));
}
}
