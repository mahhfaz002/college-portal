<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Attendance;
use App\Models\ClassAttendanceLog;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Show the attendance marking page.
     */
    public function index(Request $request)
    {
        $selectedClass = $request->query('class');
        $date = $request->query('date', date('Y-m-d'));

        // Get unique class arms for the dropdown
        $classes = Student::distinct()->pluck('class_arm');

        $students = [];
        if ($selectedClass) {
            $students = Student::where('class_arm', $selectedClass)
                ->with(['attendances' => function($query) use ($date) {
                    $query->where('attendance_date', $date);
                }])
                ->orderBy('full_name', 'asc')
                ->get();
        }

        return view('attendance.index', compact('students', 'classes', 'selectedClass', 'date'));
    }

    /**
     * Save the attendance data.
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'status' => 'required|array',
            'class' => 'nullable|string',
        ]);

        $present = 0;
        foreach ($request->status as $studentId => $status) {
            Attendance::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'attendance_date' => $request->date,
                ],
                [
                    'status' => $status,
                ]
            );
            if (in_array($status, ['present', 'late'], true)) {
                $present++;
            }
        }

        // Log that this teacher took attendance for this class today, so the
        // principal can see who is active and which classes were covered.
        $className = $request->input('class')
            ?? optional(Student::find(array_key_first($request->status)))->class_arm;

        if ($className) {
            ClassAttendanceLog::updateOrCreate(
                [
                    'user_id'   => auth()->id(),
                    'class_arm' => $className,
                    'log_date'  => $request->date,
                ],
                [
                    'present_count' => $present,
                    'total_count'   => count($request->status),
                    'taken_at'      => now(),
                ]
            );
        }

        return redirect()->back()->with('success', 'Attendance recorded successfully for ' . $request->date);
    }
    public function report(Request $request)
{
    $selectedClass = $request->query('class');
    $month = $request->query('month', date('m'));
    $year = $request->query('year', date('Y'));

    $classes = Student::distinct()->pluck('class_arm');
    $reportData = [];

    if ($selectedClass) {
        $students = Student::where('class_arm', $selectedClass)->get();

        foreach ($students as $student) {
            $present = Attendance::where('student_id', $student->id)
                ->whereMonth('attendance_date', $month)
                ->whereYear('attendance_date', $year)
                ->whereIn('status', ['present', 'late'])
                ->count();

            $totalDays = Attendance::whereHas('student', function($q) use ($selectedClass) {
                    $q->where('class_arm', $selectedClass);
                })
                ->whereMonth('attendance_date', $month)
                ->whereYear('attendance_date', $year)
                ->distinct('attendance_date')
                ->count();

            $reportData[] = [
                'name' => $student->full_name,
                'present' => $present,
                'total' => $totalDays,
                'percentage' => $totalDays > 0 ? round(($present / $totalDays) * 100, 1) : 0
            ];
        }
    }

    return view('attendance.report', compact('classes', 'reportData', 'selectedClass', 'month', 'year'));
}
}