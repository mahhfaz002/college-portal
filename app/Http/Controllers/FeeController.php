<?php

namespace App\Http\Controllers;

use App\Models\FeeBill;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeeController extends Controller
{
    /**
     * Bursary hub: bill students, run class-wide charges, see outstanding.
     */
    public function index(Request $request)
    {
        $classes = SchoolClass::orderBy('name')->pluck('name');
        $students = Student::orderBy('class_arm')->orderBy('full_name')->get();

        $recentBills = FeeBill::with('student')->latest()->take(15)->get();

        return view('fees.index', compact('classes', 'students', 'recentBills'));
    }

    /**
     * Set / add a fee for a single student.
     */
    public function storeStudentFee(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'title'      => 'required|string|max:255',
            'amount'     => 'required|numeric|min:1',
        ]);

        $student = Student::findOrFail($data['student_id']);
        $this->createBill($student, $data['title'], (float) $data['amount']);

        return back()->with('success', "Fee '{$data['title']}' (₦".number_format($data['amount'], 2).") billed to {$student->full_name}.");
    }

    /**
     * Create a fee for every student in the selected classes
     * (e.g. common entrance, WAEC).
     */
    public function storeClassFee(Request $request)
    {
        $data = $request->validate([
            'title'     => 'required|string|max:255',
            'amount'    => 'required|numeric|min:1',
            'classes'   => 'required|array|min:1',
            'classes.*' => 'string',
        ]);

        $students = Student::whereIn('class_arm', $data['classes'])->get();

        DB::transaction(function () use ($students, $data) {
            foreach ($students as $student) {
                $this->createBill($student, $data['title'], (float) $data['amount']);
            }
        });

        return back()->with('success', "'{$data['title']}' billed to {$students->count()} students across ".count($data['classes'])." class(es).");
    }

    /**
     * Create a bill and add it to the student's running balance.
     */
    private function createBill(Student $student, string $title, float $amount): FeeBill
    {
        $bill = FeeBill::create([
            'student_id' => $student->id,
            'title'      => $title,
            'term'       => setting('current_term', ''),
            'session'    => setting('current_session', ''),
            'amount'     => $amount,
            'amount_paid'=> 0,
            'status'     => 'unpaid',
            'created_by' => auth()->id(),
        ]);

        $student->increment('fees_balance', $amount);

        return $bill;
    }
}
