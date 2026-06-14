<?php

use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StaffAttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ScoreController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\AdmissionDashboardController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\TransportationController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\AlumniController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamWorkController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TermController;
use App\Support\Permissions;
use Illuminate\Support\Facades\Route;

// ==========================================
// 1. PUBLIC ROUTES (No Login Required)
// ==========================================
Route::get('/', [LandingPageController::class, 'index'])->name('home');
Route::view('/about', 'about')->name('about');
Route::view('/contact', 'contact')->name('contact');

// Admission Form (Prospective Students)
Route::get('/apply', [ApplicantController::class, 'showForm'])->name('admission.form');
Route::post('/apply', [ApplicantController::class, 'submit'])
    ->middleware('throttle:6,1')->name('admission.submit'); // spam guard on public form

// Student self-onboarding (existing students create their own account).
Route::get('/student/login', fn () => view('auth.student_login'))->name('student.login');
Route::get('/student/register', [\App\Http\Controllers\StudentSelfRegistrationController::class, 'showForm'])->name('student.register');
Route::post('/student/register', [\App\Http\Controllers\StudentSelfRegistrationController::class, 'store'])
    ->middleware('throttle:6,1')->name('student.register.store');

// Online payments (Paystack). Public: the application fee is paid before the
// applicant account exists. Gateway init/callback look invoices up by id/ref.
Route::get('/pay/{invoice}/checkout', [\App\Http\Controllers\GatewayPaymentController::class, 'checkout'])->name('payments.checkout');
Route::get('/pay/{invoice}', [\App\Http\Controllers\GatewayPaymentController::class, 'initialize'])->name('payments.initialize');
Route::get('/payments/callback', [\App\Http\Controllers\GatewayPaymentController::class, 'callback'])->name('payments.callback');
// Server-to-server webhook (signature-verified; CSRF-exempt in bootstrap/app.php).
Route::post('/paystack/webhook', [\App\Http\Controllers\GatewayPaymentController::class, 'webhook'])->name('paystack.webhook');
Route::get('/pay/{invoice}/sandbox', [\App\Http\Controllers\GatewayPaymentController::class, 'sandbox'])->name('payments.sandbox');


// ==========================================
// 2. PASSWORD SECURITY CHECK (Login Required)
// ==========================================
// These routes MUST be outside the 'force.password.change' middleware
// to avoid the infinite redirect loop.
Route::middleware(['auth'])->group(function () {
    Route::get('/change-password', [PasswordChangeController::class, 'showChangeForm'])->name('password.change.notice');
    Route::post('/change-password', [PasswordChangeController::class, 'updatePassword'])->name('password.change.update');
});


// ==========================================
// 3. FULLY PROTECTED ROUTES (Locked until Password Changed)
// ==========================================
Route::middleware(['auth', 'verified', 'force.password.change', 'platform.fee', 'readonly'])->group(function () {

    // --- Core Dashboard ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // --- Online-payment invoice receipt (owner or finance staff) ---
    Route::get('/invoices/{invoice}/receipt', [\App\Http\Controllers\InvoiceController::class, 'receipt'])->name('invoices.receipt');

    // --- Platform super-admin (cross-college analytics + college registration) ---
    Route::middleware('role:superadmin')->group(function () {
        $PF = \App\Http\Controllers\PlatformController::class;
        Route::get('/platform', [$PF, 'dashboard'])->name('platform.dashboard');
        Route::get('/platform/stats', [$PF, 'liveStats'])->name('platform.stats');
        Route::get('/platform/colleges', [$PF, 'colleges'])->name('platform.colleges');
        Route::get('/platform/colleges/register', [$PF, 'create'])->name('platform.register');
        Route::post('/platform/colleges', [$PF, 'store'])->name('platform.colleges.store');
        Route::get('/platform/colleges/{college}', [$PF, 'show'])->name('platform.colleges.show');
        Route::put('/platform/colleges/{college}', [$PF, 'update'])->name('platform.colleges.update');
        Route::post('/platform/colleges/{college}/admins', [$PF, 'addAdmin'])->name('platform.colleges.admins.add');
        Route::delete('/platform/colleges/{college}/admins/{user}', [$PF, 'removeAdmin'])->name('platform.colleges.admins.remove');
        Route::post('/platform/colleges/{college}/admins/{user}/reset', [$PF, 'resetAdmin'])->name('platform.colleges.admins.reset');
        Route::post('/platform/colleges/{college}/toggle', [$PF, 'toggle'])->name('platform.colleges.toggle');
        Route::delete('/platform/colleges/{college}', [$PF, 'destroy'])->name('platform.colleges.destroy');
    });

    // Gate redirect target for students who haven't paid the platform fee.
    Route::get('/platform/fee', [\App\Http\Controllers\StudentSelfRegistrationController::class, 'pay'])->name('platform.fee.pay');

    // --- Notifications (aggregated, role-aware) ---
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');


    // --- Admission Management (legacy panel) — registrar/admin + ICT only ---
    Route::middleware('role:registrar,proprietor,admission_officer,mis')->group(function () {
        Route::get('/admin/dashboard', [AdmissionDashboardController::class, 'index'])->name('admin.dashboard');
    });
    Route::middleware('role:'.Permissions::middleware('manage_admissions'))->group(function () {
        Route::post('/admin/applicant/{id}/status', [AdmissionDashboardController::class, 'updateStatus'])->name('admin.updateStatus');
    });

    // --- User Profile ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- Student Management ---
    // Viewing student records is for STAFF only (the 'student' role is absent
    // from view_students) — prevents a pupil from opening another pupil's
    // record/report by guessing the ID. Ordering matters so /create is not
    // captured by the /{student} wildcard.
    // Create form must be registered before the /{student} wildcard, but stays
    // Students self-register & onboard themselves now — staff can no longer
    // create/admit student accounts (students.create/store removed).
    Route::middleware('role:'.Permissions::middleware('edit_students'))->group(function () {
        Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    });

    Route::middleware('role:'.Permissions::middleware('view_students'))->group(function () {
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::get('/students/{student}/report-card', [StudentController::class, 'reportCard'])->name('students.report');
        Route::get('/students/{student}/id-card', [StudentController::class, 'idCard'])->name('students.id-card');
    });

    // Writes — MIS only (proprietor is blocked globally by readonly).
    Route::middleware('role:'.Permissions::middleware('manage_students'))->group(function () {
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    });

    // Edit student info / class correction / promotion — MIS.
    Route::middleware('role:'.Permissions::middleware('edit_students'))->group(function () {
        Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::patch('/students/{student}', [StudentController::class, 'update']);
        Route::get('/promotion', [StudentController::class, 'promotionForm'])->name('students.promotion');
        Route::post('/promotion', [StudentController::class, 'promote'])->name('students.promote');
    });

    // Teacher remark on a student's report card.
    Route::middleware('role:'.Permissions::middleware('enter_scores'))->group(function () {
        Route::post('/students/{student}/remark', [StudentController::class, 'saveRemark'])->name('students.remark');
    });

    // --- Subjects --- (bursar excluded)
    Route::middleware('role:'.Permissions::middleware('view_subjects'))->group(function () {
        Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
    });
    Route::middleware('role:'.Permissions::middleware('manage_subjects'))->group(function () {
        Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
        Route::put('/subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');

        // Academic Secretary — batch course builder (cascading + per-level).
        Route::get('/courses/build', [\App\Http\Controllers\CourseBuilderController::class, 'index'])->name('courses.builder');
        Route::get('/courses/build/list', [\App\Http\Controllers\CourseBuilderController::class, 'list'])->name('courses.builder.list');
        Route::post('/courses/build', [\App\Http\Controllers\CourseBuilderController::class, 'save'])->name('courses.builder.save');
    });

    // --- Departments & Programs (Registrar owns the academic structure) ---
    Route::middleware('role:'.Permissions::middleware('view_departments'))->group(function () {
        Route::get('/departments', [\App\Http\Controllers\DepartmentController::class, 'index'])->name('departments.index');
        Route::get('/departments/browse', [\App\Http\Controllers\DepartmentController::class, 'browse'])->name('departments.browse');
        Route::get('/programs', [\App\Http\Controllers\ProgramController::class, 'index'])->name('programs.index');
    });
    Route::middleware('role:'.Permissions::middleware('manage_departments'))->group(function () {
        Route::post('/departments', [\App\Http\Controllers\DepartmentController::class, 'store'])->name('departments.store');
        Route::put('/departments/{department}', [\App\Http\Controllers\DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [\App\Http\Controllers\DepartmentController::class, 'destroy'])->name('departments.destroy');
        Route::post('/programs', [\App\Http\Controllers\ProgramController::class, 'store'])->name('programs.store');
        Route::put('/programs/{program}', [\App\Http\Controllers\ProgramController::class, 'update'])->name('programs.update');
        Route::delete('/programs/{program}', [\App\Http\Controllers\ProgramController::class, 'destroy'])->name('programs.destroy');

        // MIS academic-structure builder (section → department → courses of study).
        $MIS = \App\Http\Controllers\MisStructureController::class;
        Route::get('/academic-structure', [$MIS, 'index'])->name('structure.index');
        Route::post('/academic-structure', [$MIS, 'store'])->name('structure.store');
        Route::post('/academic-structure/{department}/courses', [$MIS, 'addCourse'])->name('structure.courses.add');
        Route::put('/academic-structure/courses/{program}', [$MIS, 'updateCourse'])->name('structure.courses.update');
        Route::delete('/academic-structure/courses/{program}', [$MIS, 'destroyCourse'])->name('structure.courses.destroy');
        Route::delete('/academic-structure/{department}', [$MIS, 'destroyDepartment'])->name('structure.departments.destroy');
    });

    // --- Academic Secretary: assign courses to lecturers ---
    Route::middleware('role:'.Permissions::middleware('assign_courses'))->group(function () {
        $AC = \App\Http\Controllers\AcademicSecretaryController::class;
        // Read-only browse screens.
        Route::get('/academic/courses', [$AC, 'coursesList'])->name('academic.courses');
        Route::get('/academic/departments', [$AC, 'departments'])->name('academic.departments');
        // Course-centric + lecturer-centric assignment screens.
        Route::get('/academic/assign', [$AC, 'assign'])->name('academic.assign');
        Route::get('/academic/staff', [$AC, 'staff'])->name('academic.staff');

        Route::post('/course-assignments', [\App\Http\Controllers\CourseAssignmentController::class, 'store'])->name('course-assignments.store');
        Route::post('/course-assignments/batch', [\App\Http\Controllers\CourseAssignmentController::class, 'storeBatch'])->name('course-assignments.batch');
        Route::delete('/course-assignments', [\App\Http\Controllers\CourseAssignmentController::class, 'destroy'])->name('course-assignments.destroy');
    });

    // Score entry (class + subject sheet). Single canonical route name.
    Route::middleware(['role:lecturer,exam_officer,registrar,proprietor'])->group(function () {
        Route::get('/scores/entry', [ScoreController::class, 'create'])->name('scores.create');
        Route::post('/scores/store', [ScoreController::class, 'store'])->name('scores.store');
    });

    // --- Fees & Finance --- Payment Orders (Paystack) is the single billing tool.
    Route::middleware('role:'.Permissions::middleware('manage_fees'))->group(function () {
        Route::get('/students/{student}/pay', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('/students/{student}/pay', [PaymentController::class, 'store'])->name('payments.store');

        // Bursar creates a payment order and fans it out to invoices.
        Route::post('/fees/orders', [\App\Http\Controllers\FeeOrderController::class, 'store'])->name('fees.orders.store');

        // Printables archive — student receipts + staff payslips.
        $PR = \App\Http\Controllers\PrintablesController::class;
        Route::get('/printables', [$PR, 'index'])->name('printables.index');
        Route::get('/printables/student/{student}/receipts', [$PR, 'studentReceipts'])->name('printables.student');
        Route::get('/printables/staff/{user}/payslips', [$PR, 'staffPayslips'])->name('printables.staff');
    });

    // Payment Orders list/detail viewable by finance + oversight roles
    // (the create form inside is gated to manage_fees in the view).
    Route::middleware('role:'.Permissions::middleware('view_fees'))->group(function () {
        Route::get('/fees/orders', [\App\Http\Controllers\FeeOrderController::class, 'index'])->name('fees.orders.index');
        Route::get('/fees/orders/{feeOrder}', [\App\Http\Controllers\FeeOrderController::class, 'show'])->name('fees.orders.show');
    });
    // Receipts — finance/oversight staff + the owning student (authorized in controller).
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'show'])->name('payments.receipt');
    Route::get('/payments/{payment}/receipt/pdf', [PaymentController::class, 'downloadReceipt'])->name('payments.receipt.pdf');

    // --- Attendance --- (bursar & ICT excluded)
    Route::middleware('role:'.Permissions::middleware('view_attendance'))->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');
    });
    Route::middleware('role:'.Permissions::middleware('take_attendance'))->group(function () {
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    });

    // --- Staff clock in / out ---
    Route::middleware('role:'.Permissions::middleware('clock_attendance'))->group(function () {
        Route::post('/clock/in', [StaffAttendanceController::class, 'clockIn'])->name('clock.in');
        Route::post('/clock/out', [StaffAttendanceController::class, 'clockOut'])->name('clock.out');
    });

    // --- Teacher attendance oversight (Principal/Proprietor) ---
    Route::middleware('role:'.Permissions::middleware('view_staff_attendance'))->group(function () {
        Route::get('/staff-attendance', [StaffAttendanceController::class, 'report'])->name('staff.attendance');
    });

    // --- Reports ---
    Route::get('/reports/download/{studentId}', [ReportController::class, 'downloadPdf'])->name('reports.download');

    // --- Announcements / Communications (everyone can read) ---
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::middleware(['role:proprietor,mis'])->group(function () {
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
    });

    // --- College Settings / Branding --- MIS only (proprietor is view-only oversight).
    Route::middleware(['role:mis'])->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

    // --- Support tickets ---
    Route::get('/support', [SupportTicketController::class, 'index'])->name('support.index');
    Route::post('/support', [SupportTicketController::class, 'store'])
        ->middleware('throttle:20,1')->name('support.store');
    Route::middleware('role:'.Permissions::middleware('handle_tickets'))->group(function () {
        Route::put('/support/{ticket}', [SupportTicketController::class, 'update'])->name('support.update');
    });
    Route::middleware('role:'.Permissions::middleware('reset_passwords'))->group(function () {
        Route::post('/support/reset-password/{user}', [SupportTicketController::class, 'resetPassword'])->name('support.reset-password');
    });

    // --- Timetable --- (everyone views; Principal alone generates & approves)
    Route::get('/timetable', [TimetableController::class, 'index'])->name('timetable.index');
    Route::middleware('role:'.Permissions::middleware('manage_timetable'))->group(function () {
        Route::post('/timetable/generate', [TimetableController::class, 'generate'])->name('timetable.generate');
        Route::post('/timetable/{plan}/approve', [TimetableController::class, 'approve'])->name('timetable.approve');
        Route::delete('/timetable/{plan}', [TimetableController::class, 'destroy'])->name('timetable.destroy');
    });

    // --- Term / Session control (Principal) ---
    Route::middleware('role:'.Permissions::middleware('manage_term'))->group(function () {
        Route::post('/term', [TermController::class, 'update'])->name('term.update');
        Route::post('/term/clear-assignments', [TermController::class, 'clearAssignments'])->name('term.clear-assignments');
    });

    // --- Student Affairs ---
    Route::middleware('role:student_affairs,registrar,proprietor')->group(function () {
        Route::post('/affairs/cases', [\App\Http\Controllers\StudentAffairsController::class, 'store'])->name('affairs.cases.store');
        Route::post('/affairs/cases/{case}/resolve', [\App\Http\Controllers\StudentAffairsController::class, 'resolve'])->name('affairs.cases.resolve');
        Route::delete('/affairs/cases/{case}', [\App\Http\Controllers\StudentAffairsController::class, 'destroy'])->name('affairs.cases.destroy');
    });

    // --- Office Secretary (correspondence register) ---
    Route::middleware('role:office_secretary,registrar,proprietor')->group(function () {
        Route::post('/office/correspondence', [\App\Http\Controllers\OfficeSecretaryController::class, 'store'])->name('office.correspondence.store');
        Route::post('/office/correspondence/{correspondence}/status', [\App\Http\Controllers\OfficeSecretaryController::class, 'updateStatus'])->name('office.correspondence.status');
        Route::delete('/office/correspondence/{correspondence}', [\App\Http\Controllers\OfficeSecretaryController::class, 'destroy'])->name('office.correspondence.destroy');
    });

    // --- Library --- (librarian + academic oversight)
    Route::middleware('role:librarian,proprietor,mis')->group(function () {
        Route::get('/library', [LibraryController::class, 'index'])->name('library.index');
        Route::post('/library/issue', [LibraryController::class, 'issueBook'])->name('library.issue');
        Route::post('/library/return/{record}', [LibraryController::class, 'returnBook'])->name('library.return');
        Route::post('/library/books', [LibraryController::class, 'storeBook'])->name('library.books.store');
        Route::delete('/library/books/{book}', [LibraryController::class, 'destroyBook'])->name('library.books.destroy');
    });

    // ==========================================
    // EXAM WORKFLOW
    // ==========================================

    // Online student exam-taking has been removed from the platform.

    // -- Exam officer: create / store (before {exam} wildcard) --
    Route::middleware('role:'.Permissions::middleware('manage_exams'))->group(function () {
        Route::get('/exams/create', [ExamController::class, 'create'])->name('exams.create');
        Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');

        // Exam Mode (countdown timers + notification).
        Route::post('/exam-mode', [\App\Http\Controllers\ExamModeController::class, 'activate'])->name('exam-mode.activate');
        Route::post('/exam-mode/{examCycle}/close', [\App\Http\Controllers\ExamModeController::class, 'close'])->name('exam-mode.close');
    });

    // -- Teacher: author questions + grade --
    Route::middleware('role:'.Permissions::middleware('author_questions'))->group(function () {
        Route::get('/my-exam-courses', [ExamWorkController::class, 'myExams'])->name('exams.my');
        Route::get('/exam-questions-template', [ExamWorkController::class, 'template'])->name('exams.questions.template');
        Route::get('/set-questions/{subject}', [ExamWorkController::class, 'openCourse'])->name('exams.open');
        Route::get('/exams/{exam}/questions', [ExamWorkController::class, 'questions'])->name('exams.questions');
        Route::post('/exams/{exam}/questions', [ExamWorkController::class, 'storeQuestion'])->name('exams.questions.store');
        Route::post('/exams/{exam}/theory', [ExamWorkController::class, 'storeTheory'])->name('exams.theory.store');
        Route::post('/exams/{exam}/questions/import', [ExamWorkController::class, 'importCsv'])->name('exams.questions.import');
        Route::post('/exams/{exam}/submit', [ExamWorkController::class, 'submitToOfficer'])->name('exams.submit');
        Route::delete('/exam-questions/{question}', [ExamWorkController::class, 'deleteQuestion'])->name('exams.questions.delete');
        Route::get('/exams/{exam}/grade', [ExamWorkController::class, 'grade'])->name('exams.grade');
        Route::post('/exams/{exam}/grade', [ExamWorkController::class, 'saveGrades'])->name('exams.grade.save');
    });

    // -- Exam officer: lifecycle actions --
    Route::middleware('role:'.Permissions::middleware('manage_exams'))->group(function () {
        Route::post('/exams/{exam}/eligibility/{student}', [ExamController::class, 'toggleEligibility'])->name('exams.eligibility');
        Route::post('/exams/{exam}/release', [ExamController::class, 'release'])->name('exams.release');
        Route::post('/exams/{exam}/close', [ExamController::class, 'close'])->name('exams.close');
        Route::post('/exams/{exam}/approve', [ExamController::class, 'approve'])->name('exams.approve');
        Route::post('/scores/{score}/edit', [ExamController::class, 'updateScore'])->name('scores.update');
        Route::post('/exam-queries/{query}/resolve', [ExamController::class, 'resolveQuery'])->name('exams.queries.resolve');
    });

    // -- Exam viewing / oversight (officer, ICT, principal, proprietor) --
    Route::middleware('role:'.Permissions::middleware('view_exams'))->group(function () {
        Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
        Route::get('/exam-queries', [ExamController::class, 'queries'])->name('exams.queries');
        Route::get('/exams/{exam}/compile', [ExamController::class, 'compile'])->name('exams.compile');
        Route::get('/exams/{exam}', [ExamController::class, 'show'])->name('exams.show');
    });

    // --- Payroll / Payslips ---
    // Bursar runs payroll; Principal only reviews (flag/approve), cannot edit amounts.
    Route::middleware('role:'.Permissions::middleware('manage_payroll'))->group(function () {
        Route::get('/payroll', [PayslipController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/{user}/edit', [PayslipController::class, 'edit'])->name('payroll.edit');
        Route::post('/payroll/{user}', [PayslipController::class, 'store'])->name('payroll.store');
        Route::post('/payroll-submit', [PayslipController::class, 'submit'])->name('payroll.submit');
        Route::post('/payroll/{payslip}/pay', [PayslipController::class, 'pay'])->name('payroll.pay');
        Route::get('/payroll/{payslip}/slip', [PayslipController::class, 'show'])->name('payroll.slip');
        Route::get('/payroll/{payslip}/slip/pdf', [PayslipController::class, 'downloadSlip'])->name('payroll.slip.pdf');
    });
    Route::middleware('role:'.Permissions::middleware('review_payroll'))->group(function () {
        Route::get('/payroll-review', [PayslipController::class, 'review'])->name('payroll.review');
        Route::post('/payroll/{payslip}/approve', [PayslipController::class, 'approve'])->name('payroll.approve');
        Route::post('/payroll/{payslip}/flag', [PayslipController::class, 'flag'])->name('payroll.flag');
    });

    // --- Management-only modules: Transport, Alumni ---
    // (HR/Payroll moved to the dedicated payslip workflow below — bursar + principal only.)
    Route::middleware(['role:proprietor,mis,bursar'])->group(function () {
        Route::get('/transport', [TransportationController::class, 'index'])->name('transport.index');
        Route::post('/transport/assign', [TransportationController::class, 'assignStudent'])->name('transport.assign');

        Route::get('/alumni', [AlumniController::class, 'index'])->name('alumni.index');
        Route::get('/alumni/search', [AlumniController::class, 'search'])->name('alumni.search');
        Route::post('/alumni/register', [AlumniController::class, 'register'])->name('alumni.register');
    });

    // --- ROLE-BASED ACCESS (Sub-Groups) ---

    // --- Staff / Teacher Management ---
    // Viewing the staff directory & detail/ID card: Principal + Proprietor (oversight).
    Route::middleware('role:'.Permissions::middleware('view_staff'))->group(function () {
        Route::get('/staff', [UserController::class, 'index'])->name('staff.index');
        Route::get('/staff/{user}', [UserController::class, 'show'])->name('staff.show');
        Route::get('/staff/{user}/id-card', [UserController::class, 'idCard'])->name('staff.id-card');
    });

    // --- Class registry management (Principal + ICT) ---
    Route::middleware('role:'.Permissions::middleware('manage_classes'))->group(function () {
        Route::get('/classes', [ClassController::class, 'index'])->name('classes.index');
        Route::post('/classes', [ClassController::class, 'store'])->name('classes.store');
        Route::post('/classes/{schoolClass}/toggle', [ClassController::class, 'toggle'])->name('classes.toggle');
    });

    // Creating / editing / assigning / deleting staff: Principal only.
    Route::middleware('role:'.Permissions::middleware('manage_staff'))->group(function () {
        Route::get('/staff-register', [UserController::class, 'create'])->name('staff.create');
        Route::post('/staff', [UserController::class, 'store'])->name('staff.store');
        Route::get('/staff/{user}/edit', [UserController::class, 'edit'])->name('staff.edit');
        Route::put('/staff/{user}', [UserController::class, 'update'])->name('staff.update');
        Route::post('/staff/{user}/assignments', [UserController::class, 'updateAssignments'])->name('staff.assignments');
        Route::delete('/staff/{user}', [UserController::class, 'destroy'])->name('staff.destroy');
    });

    // Superadmin Switching
    Route::get('/superadmin/switchboard', [DashboardController::class, 'switchboard'])
        ->name('superadmin.switchboard')
        ->middleware('role:proprietor,mis');

    // Inventory register (MIS / office secretary write; proprietor + provost read-only)
    Route::middleware('role:'.Permissions::middleware('manage_inventory').',proprietor,provost')->group(function () {
        Route::resource('inventory', InventoryItemController::class);
    });

    // Applications list — Registrar & Admission Officer (+ proprietor view)
    Route::middleware('role:'.Permissions::middleware('view_applications'))->group(function () {
        Route::get('/admin/admissions', [ApplicantController::class, 'index'])->name('admission.admin');
    });

    // (Staff no longer create applicants manually — applicants apply online.)

    // Admin/Registrar approves or rejects (ICT can no longer approve).
    Route::middleware('role:'.Permissions::middleware('manage_admissions'))->group(function () {
        Route::post('/admin/admissions/{id}/approve', [ApplicantController::class, 'approve'])->name('admission.approve');
        Route::post('/admin/admissions/{id}/reject', [ApplicantController::class, 'reject'])->name('admission.reject');
    });

    // ===== Phase 3 — admission → acceptance → registration → HOD approval =====
    $AW = \App\Http\Controllers\AdmissionWorkflowController::class;

    // Registrar: review paid applications, offer/decline admission.
    Route::middleware('role:'.Permissions::middleware('manage_admissions'))->group(function () use ($AW) {
        Route::get('/admissions/review', [$AW, 'reviewPanel'])->name('admissions.review');
        Route::post('/admissions/{applicant}/offer', [$AW, 'offer'])->name('admissions.offer');
        Route::post('/admissions/{applicant}/decline', [$AW, 'decline'])->name('admissions.decline');
    });

    // Applicant: accept / reject the offer, reapply, download letter & form.
    Route::post('/admission/accept', [$AW, 'accept'])->name('admission.accept');
    Route::post('/admission/reject', [$AW, 'rejectOffer'])->name('admission.reject.offer');
    Route::post('/admission/reapply', [$AW, 'reapply'])->name('admission.reapply');
    Route::get('/admission/letter', [$AW, 'admissionLetter'])->name('admission.letter');
    Route::get('/admission/acceptance-form', [$AW, 'acceptanceForm'])->name('admission.acceptance_form');

    // Student: registration document upload.
    Route::get('/registration', [$AW, 'registration'])->name('registration.documents');
    Route::post('/registration', [$AW, 'storeDocuments'])->name('registration.documents.store');

    // HOD / Assistant HOD: review and approve registrations.
    Route::middleware('role:hod,assistant_hod')->group(function () use ($AW) {
        Route::get('/hod/registrations', [$AW, 'hodRegistrations'])->name('hod.registrations');
        Route::post('/hod/registrations/{student}/approve', [$AW, 'hodApprove'])->name('hod.registrations.approve');
        Route::post('/hod/registrations/{student}/reject', [$AW, 'hodReject'])->name('hod.registrations.reject');

        // Department students (read-only) + resource-person (lecturer) accounts.
        $HOD = \App\Http\Controllers\HodController::class;
        Route::get('/hod/courses', [$HOD, 'courses'])->name('hod.courses');
        Route::get('/hod/students', [$HOD, 'students'])->name('hod.students');
        Route::get('/hod/students/{student}', [$HOD, 'showStudent'])->name('hod.students.show');
        Route::get('/hod/resource-persons', [$HOD, 'resourcePersons'])->name('hod.resource-persons');
        Route::post('/hod/resource-persons', [$HOD, 'storeResourcePerson'])->name('hod.resource-persons.store');
        Route::get('/hod/grading', [$HOD, 'grading'])->name('hod.grading');
        Route::post('/hod/grading', [$HOD, 'saveGrading'])->name('hod.grading.save');
    });
});

// ==========================================
// AUTHENTICATION SYSTEM
// ==========================================
require __DIR__.'/auth.php';