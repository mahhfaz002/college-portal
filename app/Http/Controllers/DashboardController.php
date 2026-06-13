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
            'superadmin'         => app(\App\Http\Controllers\PlatformController::class)->dashboard($request),
            'proprietor'         => $this->proprietorDashboard($request),
            'provost'            => $this->provostDashboard($request),      // academic head — oversight
            'registrar'          => $this->registrarDashboard($request),   // Registrar = read-only oversight + staff + admissions
            'mis'                => $this->ictDashboard($request),         // MIS (formerly ICT)
            'bursar'             => $this->accountantDashboard($request),   // Bursar = finance
            'lecturer'           => $this->teacherDashboard($request),      // Lecturer = old teacher
            'student'            => $this->studentDashboard($request),
            'applicant'          => $this->applicantDashboard($request),
            'admission_officer'  => app(\App\Http\Controllers\AdmissionOfficerController::class)->dashboard($request),
            'exam_officer'       => $this->examOfficerDashboard(),
            'hod'                => $this->hodDashboard($request),
            'assistant_hod'      => $this->hodDashboard($request),
            'academic_secretary' => $this->academicSecretaryDashboard($request),
            'student_affairs'    => app(\App\Http\Controllers\StudentAffairsController::class)->dashboard($request),
            'librarian'          => app(\App\Http\Controllers\LibraryController::class)->index(),
            'office_secretary'   => app(\App\Http\Controllers\OfficeSecretaryController::class)->dashboard($request),
            default              => $this->proprietorDashboard($request),
        };
    }

    /**
     * ROLE 1: PROPRIETOR / ADMIN (Sees Everything) — renders the rich overview.
     */
    private function proprietorDashboard(Request $request)
    {
        // Headline oversight figures.
        $studentCount = Student::count();
        $staffCount   = \App\Models\User::whereNotIn('role', ['student', 'applicant', 'superadmin'])->count();

        // Finance on the Phase-4 Invoice engine (college-scoped) — real money.
        $totalCollected   = \App\Models\Invoice::where('status', 'paid')->sum('amount');
        $totalOutstanding = \App\Models\Invoice::where('status', 'pending')->sum('amount');

        // Student headcount per department (college structure, not legacy classes).
        $deptBreakdown = Student::select('department_id', DB::raw('COUNT(*) as total'))
            ->groupBy('department_id')
            ->with('department')
            ->get()
            ->sortByDesc('total')
            ->values();

        // Top performers across the college (programme shown, not legacy class).
        $topStudents = Score::select('student_id', DB::raw('AVG(ca_score + exam_score) as average_score'))
            ->groupBy('student_id')->orderByDesc('average_score')
            ->with('student.program')->take(5)->get();

        // Most recent settled invoices.
        $recentPayments = \App\Models\Invoice::where('status', 'paid')
            ->whereNotNull('paid_at')->with('student')
            ->latest('paid_at')->take(6)->get();

        return view('dashboards.proprietor', compact(
            'studentCount',
            'staffCount',
            'totalCollected',
            'totalOutstanding',
            'deptBreakdown',
            'topStudents',
            'recentPayments'
        ));
    }

    /**
     * PROVOST — academic-head oversight. Same finance/stats as the proprietor
     * but the structure panel shows the 20 most recently registered students
     * (not the department breakdown).
     */
    private function provostDashboard(Request $request)
    {
        $studentCount = Student::count();
        $staffCount   = \App\Models\User::whereNotIn('role', ['student', 'applicant', 'superadmin'])->count();

        $totalCollected   = \App\Models\Invoice::where('status', 'paid')->sum('amount');
        $totalOutstanding = \App\Models\Invoice::where('status', 'pending')->sum('amount');

        // 20 most recently registered students.
        $recentStudents = Student::with('program')->latest()->take(20)->get();

        $topStudents = Score::select('student_id', DB::raw('AVG(ca_score + exam_score) as average_score'))
            ->groupBy('student_id')->orderByDesc('average_score')
            ->with('student.program')->take(5)->get();

        $recentPayments = \App\Models\Invoice::where('status', 'paid')
            ->whereNotNull('paid_at')->with('student')
            ->latest('paid_at')->take(6)->get();

        return view('dashboards.provost', compact(
            'studentCount', 'staffCount', 'totalCollected', 'totalOutstanding',
            'recentStudents', 'topStudents', 'recentPayments'
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

        // Today's lessons from the approved timetable.
        $todayLessons = collect();
        $plan = \App\Models\TimetablePlan::where('status', 'approved')->latest()->first();
        if ($plan) {
            $todayLessons = \App\Models\TimetableEntry::where('plan_id', $plan->id)
                ->where('teacher_id', $user->id)
                ->where('day', now()->format('l'))
                ->with('subject')->orderBy('period_no')->get();
        }

        return view('dashboards.teacher', compact(
            'students', 'studentCount', 'selectedClass', 'attendanceTaken',
            'classStatus', 'clock', 'authorExams', 'gradeExams', 'todayLessons'
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
            'role' => 'lecturer',
            'class_assigned' => $request->class_assigned,
            'subject_assigned' => $request->subject_assigned,
            'must_change_password' => true,
        ]);

        return back()->with('success', "Teacher Account Created! Email: $email | Temp Password: $randomPassword");
    }

    /**
     * BURSAR — collections overview built on the Phase-4 Invoice / FeeOrder
     * engine. Invoice is college-scoped automatically via CollegeScope, so
     * every figure here reflects only the current tenant's money movement.
     */
    private function accountantDashboard(Request $request)
    {
        $totalBilled      = \App\Models\Invoice::sum('amount');
        $totalCollected   = \App\Models\Invoice::where('status', 'paid')->sum('amount');
        $totalOutstanding = \App\Models\Invoice::where('status', 'pending')->sum('amount');
        $collectionRate   = $totalBilled > 0 ? (int) round($totalCollected / $totalBilled * 100) : 0;

        // Most recent settled invoices.
        $recentPayments = \App\Models\Invoice::where('status', 'paid')
            ->whereNotNull('paid_at')
            ->with('student')
            ->latest('paid_at')->take(6)->get();

        // Students carrying the largest unpaid balance across all their invoices.
        $topDebtors = \App\Models\Invoice::where('status', 'pending')
            ->whereNotNull('student_id')
            ->select('student_id', DB::raw('SUM(amount) as outstanding'), DB::raw('COUNT(*) as bills'))
            ->groupBy('student_id')
            ->orderByDesc('outstanding')
            ->with('student')
            ->take(10)->get();

        // Per-order collection progress (latest orders).
        $orders = \App\Models\FeeOrder::withCount([
                'invoices',
                'invoices as paid_count' => fn ($q) => $q->where('status', 'paid'),
            ])
            ->withSum(['invoices as collected' => fn ($q) => $q->where('status', 'paid')], 'amount')
            ->latest()->take(6)->get();

        return view('dashboards.accountant', compact(
            'totalBilled', 'totalCollected', 'totalOutstanding', 'collectionRate',
            'recentPayments', 'topDebtors', 'orders'
        ));
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

        $announcements = \App\Models\Announcement::visibleTo('student', $student->class_arm)
            ->with('author')->latest()->take(5)->get();

        // Today's lessons for the student's class.
        $todayLessons = collect();
        $ttPlan = \App\Models\TimetablePlan::where('status', 'approved')->latest()->first();
        if ($ttPlan && $student->class_arm) {
            $todayLessons = \App\Models\TimetableEntry::where('plan_id', $ttPlan->id)
                ->where('class_arm', $student->class_arm)
                ->where('day', now()->format('l'))
                ->with('subject', 'teacher')->orderBy('period_no')->get();
        }
        $totalDays = Attendance::where('student_id', $student->id)->count();
        $daysPresent = Attendance::where('student_id', $student->id)->where('status', 'present')->count();
        $attendanceRate = $totalDays > 0 ? round(($daysPresent / $totalDays) * 100) : 100;

        // Online payment orders / invoices assigned to this student (Phase 4).
        $invoices = \App\Models\Invoice::where('student_id', $student->id)->latest()->get();

        return view('dashboards.student', compact('student', 'scores', 'payments', 'attendanceRate', 'announcements', 'todayLessons', 'invoices'));
    }

    /**
     * REGISTRAR — read-only oversight + staff management + shared admission queue.
     */
    private function registrarDashboard(Request $request)
    {
        $stats = [
            'students'     => Student::count(),
            'applications' => \App\Models\Applicant::where('payment_status', 'paid')->count(),
            'queue'        => \App\Models\Applicant::where('payment_status', 'paid')
                                ->whereIn('application_status', ['submitted', 'offer_rejected'])->count(),
            'staff'        => \App\Models\User::whereNotIn('role', ['student', 'applicant'])->count(),
        ];

        return view('dashboards.registrar', compact('stats'));
    }

    /**
     * APPLICANT — limited dashboard: application status, admission decision and
     * fees/invoices only. Full student features unlock after registration (Phase 3).
     */
    private function applicantDashboard(Request $request)
    {
        $user = auth()->user();
        $applicant = \App\Models\Applicant::where('user_id', $user->id)->first();
        $invoices = \App\Models\Invoice::where('applicant_id', optional($applicant)->id)
            ->latest()->get();

        return view('dashboards.applicant', compact('applicant', 'invoices'));
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
        $unassignedTeachers = \App\Models\User::where('role', 'lecturer')
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
        $teacherTotal = \App\Models\User::where('role', 'lecturer')->count();
        $activeTeachers = \App\Models\User::where('role', 'lecturer')
            ->whereIn('id', $activeTeacherIds)->count();

        // Today's clock in/out for every teacher, shown inline on the dashboard.
        $clockToday = \App\Models\StaffAttendance::whereDate('work_date', $today)
            ->get()->keyBy('user_id');
        $teacherClock = \App\Models\User::where('role', 'lecturer')->orderBy('name')->get()
            ->map(fn ($t) => [
                'teacher'   => $t,
                'clock_in'  => optional($clockToday->get($t->id))->clock_in,
                'clock_out' => optional($clockToday->get($t->id))->clock_out,
            ]);

        // Pending payroll approvals (badge).
        $pendingPayroll = \Illuminate\Support\Facades\Schema::hasTable('payslips')
            ? \App\Models\Payslip::where('status', 'submitted')->count() : 0;

        return view('dashboards.principal', compact(
            'staffCount',
            'unassignedTeachers',
            'totalStudents',
            'attendanceToday',
            'staffList',
            'activeTeachers',
            'teacherTotal',
            'teacherClock',
            'pendingPayroll'
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

        $examCycle = \App\Models\ExamCycle::active();

        return view('dashboards.exam_officer', compact(
            'totalStudents', 'subjectsCount', 'exams', 'openQueries', 'pendingGrading', 'roster', 'examCycle'
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

    /**
     * HEAD OF DEPARTMENT / ASSISTANT HOD.
     * Core action (Phase 1): see students in the department and the registrations
     * awaiting HOD approval. Real approval wiring lands in Phase 3.
     */
    private function hodDashboard(Request $request)
    {
        $user = auth()->user();
        $departmentId = $user->department_id;

        $programs = \App\Models\Program::where('department_id', $departmentId)->get();
        $programIds = $programs->pluck('id');

        $studentCount = Student::whereIn('program_id', $programIds)->count();
        $pendingApprovals = Student::whereIn('program_id', $programIds)
            ->where('registration_status', 'pending_hod')->count();
        $courses = \App\Models\Subject::whereIn('program_id', $programIds)->count();
        $lecturers = \App\Models\User::where('role', 'lecturer')
            ->where('department_id', $departmentId)->count();

        $department = \App\Models\Department::find($departmentId);

        return view('dashboards.hod', compact(
            'department', 'programs', 'studentCount', 'pendingApprovals', 'courses', 'lecturers'
        ));
    }

    /**
     * ACADEMIC SECRETARY.
     * Core action (Phase 1): view all courses + lecturers and assign a course
     * to an academic lecturer (uses the existing subject_teacher pivot).
     */
    private function academicSecretaryDashboard(Request $request)
    {
        $courses = \App\Models\Subject::with('teachers')->orderBy('name')->get();
        $lecturers = \App\Models\User::where('role', 'lecturer')->orderBy('name')->get();
        $departments = \App\Models\Department::orderBy('name')->get();

        $totalCourses = $courses->count();
        $assignedCourses = $courses->filter(fn ($c) => $c->teachers->isNotEmpty())->count();

        return view('dashboards.academic_secretary', compact(
            'courses', 'lecturers', 'departments', 'totalCourses', 'assignedCourses'
        ));
    }

    /**
     * Generic shell dashboard for the lighter staff roles (Student Affairs,
     * Librarian, Office Secretary, Assistant HOD fallback). Phase 1 = shell +
     * headline figures; each role's full feature set arrives in later phases.
     */
    private function simpleDashboard(string $role)
    {
        $stats = [
            'staffCount'   => \App\Models\User::where('role', '!=', 'student')->count(),
            'studentCount' => Student::count(),
        ];

        // Librarian gets live library figures if the tables exist.
        if ($role === 'librarian' && \Illuminate\Support\Facades\Schema::hasTable('books')) {
            $stats['books'] = \App\Models\Book::count();
            $stats['borrowed'] = \Illuminate\Support\Facades\Schema::hasTable('borrow_records')
                ? \App\Models\BorrowRecord::whereNull('returned_at')->count() : 0;
        }

        return view('dashboards.' . $role, ['role' => $role, 'stats' => $stats]);
    }
}
