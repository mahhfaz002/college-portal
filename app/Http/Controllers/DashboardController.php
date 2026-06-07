<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Payment;
use App\Models\Score;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * The Multi-Role Traffic Warden.
     * Every authenticated user hits /dashboard; we render the right view per role.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        return match ($user->role) {
            'proprietor'   => $this->proprietorDashboard($request),
            'principal'    => $this->principalDashboard($request),
            'admin'        => $this->adminDashboard($request),
            'ict'          => $this->ictDashboard($request),
            'accountant'   => $this->accountantDashboard($request),
            'teacher'      => $this->teacherDashboard($request),
            'student'      => $this->studentDashboard($request),
            'exam_officer' => $this->examOfficerDashboard(),
            default        => $this->proprietorDashboard($request),
        };
    }

    /**
     * ROLE 1: PROPRIETOR / ADMIN (Sees Everything) — renders the rich overview.
     */
    private function proprietorDashboard(Request $request)
    {
        $search = $request->query('search', '');
        $selectedClass = $request->query('class');
        $searchResults = collect();

        if ($search) {
            $searchResults = Student::where('full_name', 'LIKE', "%{$search}%")
                ->orWhere('admission_number', 'LIKE', "%{$search}%")
                ->limit(5)->get();
        }

        $studentQuery = Student::query();
        $paymentQuery = Payment::with('student');

        if ($selectedClass) {
            $studentQuery->where('class_arm', $selectedClass);
            $paymentQuery->whereHas('student', fn($q) => $q->where('class_arm', $selectedClass));
        }

        $totalRevenue = (clone $paymentQuery)->sum('amount');
        $totalDebt = (clone $studentQuery)->sum('fees_balance');
        $studentCount = (clone $studentQuery)->count();

        $topStudents = Score::select('student_id', DB::raw('AVG(ca_score + exam_score) as average_score'))
            ->groupBy('student_id')->orderByDesc('average_score')->with('student')->take(5)->get();

        $recentPayments = (clone $paymentQuery)->latest()->take(5)->get();
        $classes = Student::select('class_arm')->whereNotNull('class_arm')->distinct()->pluck('class_arm');

        // Oversight figures: total staff and per-class student headcounts.
        $staffCount = \App\Models\User::where('role', '!=', 'student')->count();
        $classBreakdown = Student::select('class_arm', DB::raw('COUNT(*) as total'))
            ->whereNotNull('class_arm')
            ->groupBy('class_arm')
            ->orderBy('class_arm')
            ->get();

        return view('dashboards.proprietor', compact(
            'totalRevenue',
            'totalDebt',
            'studentCount',
            'staffCount',
            'classBreakdown',
            'topStudents',
            'recentPayments',
            'classes',
            'selectedClass',
            'searchResults',
            'search'
        ));
    }

    private function teacherDashboard(Request $request)
    {
        $user = auth()->user();
        $user->load('classes', 'subjects');

        // Classes from the pivot, falling back to the legacy single column.
        $classNames = $user->classes->pluck('name');
        if ($classNames->isEmpty() && $user->class_assigned) {
            $classNames = collect([$user->class_assigned]);
        }

        $today = date('Y-m-d');

        // Per-class "attendance taken today?" status.
        $classStatus = $classNames->map(function ($name) use ($today, $user) {
            $studentIds = Student::where('class_arm', $name)->pluck('id');
            $taken = \App\Models\ClassAttendanceLog::where('user_id', $user->id)
                ->where('class_arm', $name)
                ->whereDate('log_date', $today)
                ->exists();
            return [
                'name' => $name,
                'student_count' => $studentIds->count(),
                'attendance_taken' => $taken,
            ];
        });

        $selectedClass = $classNames->first() ?? 'Not Assigned';
        $students = $selectedClass !== 'Not Assigned'
            ? Student::where('class_arm', $selectedClass)->get()
            : collect();
        $studentCount = $students->count();
        $attendanceTaken = $classStatus->firstWhere('name', $selectedClass)['attendance_taken'] ?? false;

        // Today's clock record.
        $clock = \App\Models\StaffAttendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        // Exam tasks for the subjects this teacher teaches.
        $subjectIds = $user->subjects->pluck('id');
        $authorExams = collect();
        $gradeExams = collect();
        if ($subjectIds->isNotEmpty()) {
            $authorExams = \App\Models\Exam::whereIn('subject_id', $subjectIds)
                ->where('status', 'draft')->with('subject')->get();
            $gradeExams = \App\Models\Exam::whereIn('subject_id', $subjectIds)
                ->whereIn('status', ['released', 'grading'])
                ->withCount('submissions')->with('subject')->get()
                ->filter(fn ($e) => $e->submissions_count > 0)->values();
        }

        return view('dashboards.teacher', compact(
            'students', 'studentCount', 'selectedClass', 'attendanceTaken',
            'classStatus', 'clock', 'authorExams', 'gradeExams'
        ));
    }

    public function storeTeacher(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string',
            'surname' => 'required|string',
            'class_assigned' => 'required|string',
            'subject_assigned' => 'required|string',
        ]);

        // Build a staff email from the school's configured domain (settings-driven).
        $domain = setting('staff_email_domain', 'school.test');
        $initial = strtolower(substr($request->first_name, 0, 1));
        $surname = strtolower($request->surname);
        $email = $initial . '.' . $surname . '@' . $domain;

        // Generate a random temporary password the teacher must change on first login.
        $randomPassword = \Illuminate\Support\Str::random(8);

        \App\Models\User::create([
            'name' => $request->first_name . ' ' . $request->surname,
            'email' => $email,
            'password' => \Illuminate\Support\Facades\Hash::make($randomPassword),
            'role' => 'teacher',
            'class_assigned' => $request->class_assigned,
            'subject_assigned' => $request->subject_assigned,
            'must_change_password' => true,
        ]);

        return back()->with('success', "Teacher Account Created! Email: $email | Temp Password: $randomPassword");
    }

    private function accountantDashboard(Request $request)
    {
        $totalRevenue = Payment::sum('amount');
        $totalDebt = Student::sum('fees_balance');
        $debtors = Student::where('fees_balance', '>', 0)
            ->orderByDesc('fees_balance')
            ->take(10)
            ->get();
        $recentPayments = Payment::with('student')
            ->latest()
            ->take(5)
            ->get();
        return view('dashboards.accountant', compact('totalRevenue', 'totalDebt', 'debtors', 'recentPayments'));
    }

    /**
     * ROLE 4: STUDENT
     */
    private function studentDashboard(Request $request)
    {
        $user = auth()->user();
        $student = Student::where('email', $user->email)->first();

        if (!$student) {
            return view('dashboards.student', [
                'error' => 'No student record linked to this account.',
                'student' => null,
                'scores' => collect(),
                'payments' => collect(),
                'attendanceRate' => 0,
            ]);
        }

        // Only published results are visible to students.
        $scores = Score::where('student_id', $student->id)
            ->where('status', 'published')
            ->with('subject')->get();
        $payments = Payment::where('student_id', $student->id)->latest()->take(5)->get();
        $availableExams = \App\Models\Exam::where('status', 'released')->get()
            ->filter(fn ($e) => in_array($student->class_arm, $e->class_arms, true))
            ->count();
        $totalDays = Attendance::where('student_id', $student->id)->count();
        $daysPresent = Attendance::where('student_id', $student->id)->where('status', 'present')->count();
        $attendanceRate = $totalDays > 0 ? round(($daysPresent / $totalDays) * 100) : 100;

        return view('dashboards.student', compact('student', 'scores', 'payments', 'attendanceRate', 'availableExams'));
    }

    /**
     * SUPERADMIN / PROPRIETOR SWITCHBOARD
     */
    public function switchboard()
    {
        return view('dashboards.switchboard');
    }

    private function principalDashboard(Request $request)
    {
        $staffCount = \App\Models\User::where('role', '!=', 'student')->count();
        $unassignedTeachers = \App\Models\User::where('role', 'teacher')
            ->whereNull('class_assigned')
            ->count();
        $totalStudents = Student::count();
        $attendanceToday = Attendance::where('attendance_date', date('Y-m-d'))->count();
        $staffList = \App\Models\User::where('role', '!=', 'student')
            ->latest()
            ->take(5)
            ->get();

        // How many teachers are active today (clocked in or took a class).
        $today = date('Y-m-d');
        $activeTeacherIds = \App\Models\StaffAttendance::whereDate('work_date', $today)
            ->whereNotNull('clock_in')
            ->pluck('user_id')
            ->merge(\App\Models\ClassAttendanceLog::whereDate('log_date', $today)->pluck('user_id'))
            ->unique();
        $teacherTotal = \App\Models\User::where('role', 'teacher')->count();
        $activeTeachers = \App\Models\User::where('role', 'teacher')
            ->whereIn('id', $activeTeacherIds)->count();

        return view('dashboards.principal', compact(
            'staffCount',
            'unassignedTeachers',
            'totalStudents',
            'attendanceToday',
            'staffList',
            'activeTeachers',
            'teacherTotal'
        ));
    }

    private function examOfficerDashboard()
    {
        $totalStudents = Student::count();
        $subjectsCount = \App\Models\Subject::count();
        $exams = \App\Models\Exam::with('subject')->latest()->take(8)->get();
        $openQueries = \App\Models\ResultQuery::where('status', 'open')->count();
        $pendingGrading = \App\Models\Exam::where('status', 'grading')->count();

        // Roster: each student with attendance % and fee-cleared status.
        $roster = Student::orderBy('class_arm')->orderBy('full_name')->get()->map(function ($s) {
            $present = $s->attendances()->whereIn('status', ['present', 'late'])->count();
            $total = $s->attendances()->count();
            return [
                'student' => $s,
                'attendance_pct' => $total > 0 ? (int) round($present / $total * 100) : 100,
                'fees_cleared' => $s->feesCleared(),
            ];
        });

        return view('dashboards.exam_officer', compact(
            'totalStudents', 'subjectsCount', 'exams', 'openQueries', 'pendingGrading', 'roster'
        ));
    }

    private function adminDashboard(Request $request)
    {
        $pending = \App\Models\Applicant::where('status', 'pending')->count();
        $admitted = \App\Models\Applicant::where('status', 'admitted')->count();
        $totalStudents = Student::count();
        $recentApplicants = \App\Models\Applicant::latest()->take(6)->get();

        return view('dashboards.admin', compact('pending', 'admitted', 'totalStudents', 'recentApplicants'));
    }

    private function ictDashboard(Request $request)
    {
        $totalUsers = \App\Models\User::count();
        $activeSessions = DB::table('sessions')->count();
        $latestSystemLogs = \App\Models\ActivityLog::with('user')->latest()->take(10)->get();
        $openTickets = \App\Models\SupportTicket::where('status', '!=', 'resolved')->count();
        $releasedExams = \App\Models\Exam::where('status', 'released')->count();
        $allUsers = \App\Models\User::where('role', '!=', 'student')->orderBy('name')->get();

        return view('dashboards.ict', compact(
            'totalUsers',
            'activeSessions',
            'latestSystemLogs',
            'openTickets',
            'releasedExams',
            'allUsers'
        ));
    }
}
