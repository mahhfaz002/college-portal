<?php

use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\FeeController;
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
use App\Http\Controllers\StudentExamController;
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

// Online payments (Paystack). Public: the application fee is paid before the
// applicant account exists. Gateway init/callback look invoices up by id/ref.
Route::get('/pay/{invoice}', [\App\Http\Controllers\GatewayPaymentController::class, 'initialize'])->name('payments.initialize');
Route::get('/payments/callback', [\App\Http\Controllers\GatewayPaymentController::class, 'callback'])->name('payments.callback');
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
Route::middleware(['auth', 'verified', 'force.password.change', 'readonly'])->group(function () {

    // --- Core Dashboard ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // --- Online-payment invoice receipt (owner or finance staff) ---
    Route::get('/invoices/{invoice}/receipt', [\App\Http\Controllers\InvoiceController::class, 'receipt'])->name('invoices.receipt');

    // --- Notifications (aggregated, role-aware) ---
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');


    // --- Admission Management (legacy panel) — registrar/admin + ICT only ---
    Route::middleware('role:registrar,ict,proprietor')->group(function () {
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
    Route::middleware('role:'.Permissions::middleware('view_students'))->group(function () {
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
        Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
        Route::get('/students/{student}/report-card', [StudentController::class, 'reportCard'])->name('students.report');
        Route::get('/students/{student}/id-card', [StudentController::class, 'idCard'])->name('students.id-card');
    });

    // Writes — Registrar/Admin only (proprietor is blocked globally by readonly).
    // Admit / delete students — Registrar/Admin only.
    Route::middleware('role:'.Permissions::middleware('manage_students'))->group(function () {
        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    });

    // Edit student info / class correction / promotion — Admin + ICT.
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
    });

    // --- Departments & Programs (Registrar owns the academic structure) ---
    Route::middleware('role:'.Permissions::middleware('view_departments'))->group(function () {
        Route::get('/departments', [\App\Http\Controllers\DepartmentController::class, 'index'])->name('departments.index');
        Route::get('/programs', [\App\Http\Controllers\ProgramController::class, 'index'])->name('programs.index');
    });
    Route::middleware('role:'.Permissions::middleware('manage_departments'))->group(function () {
        Route::post('/departments', [\App\Http\Controllers\DepartmentController::class, 'store'])->name('departments.store');
        Route::put('/departments/{department}', [\App\Http\Controllers\DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [\App\Http\Controllers\DepartmentController::class, 'destroy'])->name('departments.destroy');
        Route::post('/programs', [\App\Http\Controllers\ProgramController::class, 'store'])->name('programs.store');
        Route::put('/programs/{program}', [\App\Http\Controllers\ProgramController::class, 'update'])->name('programs.update');
        Route::delete('/programs/{program}', [\App\Http\Controllers\ProgramController::class, 'destroy'])->name('programs.destroy');
    });

    // --- Academic Secretary: assign courses to lecturers ---
    Route::middleware('role:'.Permissions::middleware('assign_courses'))->group(function () {
        Route::post('/course-assignments', [\App\Http\Controllers\CourseAssignmentController::class, 'store'])->name('course-assignments.store');
        Route::delete('/course-assignments', [\App\Http\Controllers\CourseAssignmentController::class, 'destroy'])->name('course-assignments.destroy');
    });

    // Score entry (class + subject sheet). Single canonical route name.
    Route::middleware(['role:lecturer,exam_officer,registrar,proprietor'])->group(function () {
        Route::get('/scores/entry', [ScoreController::class, 'create'])->name('scores.create');
        Route::post('/scores/store', [ScoreController::class, 'store'])->name('scores.store');
    });

    // --- Fees & Finance --- (Bursar/Accountant only for taking payments)
    Route::middleware('role:'.Permissions::middleware('manage_fees'))->group(function () {
        Route::get('/students/{student}/pay', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('/students/{student}/pay', [PaymentController::class, 'store'])->name('payments.store');

        // Fee billing actions
        Route::post('/fees/student', [FeeController::class, 'storeStudentFee'])->name('fees.student');
        Route::post('/fees/class', [FeeController::class, 'storeClassFee'])->name('fees.class');
    });

    // Fee hub is viewable by finance + oversight roles (writes still gated above).
    Route::middleware('role:'.Permissions::middleware('view_fees'))->group(function () {
        Route::get('/fees', [FeeController::class, 'index'])->name('fees.index');
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
    Route::middleware(['role:proprietor,registrar,ict'])->group(function () {
        Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

        // --- School Settings / Branding ---
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

    // --- Library ---
    Route::get('/library', [LibraryController::class, 'index'])->name('library.index');
    Route::post('/library/issue', [LibraryController::class, 'issueBook'])->name('library.issue');
    Route::post('/library/return/{record}', [LibraryController::class, 'returnBook'])->name('library.return');

    // ==========================================
    // EXAM WORKFLOW
    // ==========================================

    // -- Student exam taking (define before /exams/{exam} wildcards) --
    Route::middleware('role:'.Permissions::middleware('take_exams'))->group(function () {
        Route::get('/my-exams', [StudentExamController::class, 'available'])->name('myexams.available');
        Route::get('/my-exams/{exam}', [StudentExamController::class, 'take'])->name('myexams.take');
        Route::post('/my-exams/{exam}/unlock', [StudentExamController::class, 'unlock'])
            ->middleware('throttle:10,1')->name('myexams.unlock'); // brute-force guard on exam password
        Route::post('/my-exams/{exam}/submit', [StudentExamController::class, 'submit'])->name('myexams.submit');
        Route::post('/results/{score}/query', [StudentExamController::class, 'query'])->name('results.query');
    });

    // -- Exam officer: create / store (before {exam} wildcard) --
    Route::middleware('role:'.Permissions::middleware('manage_exams'))->group(function () {
        Route::get('/exams/create', [ExamController::class, 'create'])->name('exams.create');
        Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');
    });

    // -- Teacher: author questions + grade --
    Route::middleware('role:'.Permissions::middleware('author_questions'))->group(function () {
        Route::get('/exams/{exam}/questions', [ExamWorkController::class, 'questions'])->name('exams.questions');
        Route::post('/exams/{exam}/questions', [ExamWorkController::class, 'storeQuestion'])->name('exams.questions.store');
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
    Route::middleware(['role:proprietor,registrar,ict,bursar'])->group(function () {
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
        ->middleware('role:proprietor,ict');

    // Inventory + admissions list (Admin/ICT/Proprietor view)
    Route::middleware(['role:registrar,ict,proprietor'])->group(function () {
        Route::resource('inventory', InventoryItemController::class);
        Route::get('/admin/admissions', [ApplicantController::class, 'index'])->name('admission.admin');
    });

    // ICT creates admission applications (with section + class + documents).
    Route::middleware('role:'.Permissions::middleware('create_admissions'))->group(function () {
        Route::get('/admissions/apply', [ApplicantController::class, 'createApplication'])->name('admission.apply');
        Route::post('/admissions/apply', [ApplicantController::class, 'storeApplication'])
            ->middleware('throttle:30,1')->name('admission.apply.store');
    });

    // Admin/Registrar approves or rejects (ICT can no longer approve).
    Route::middleware('role:'.Permissions::middleware('manage_admissions'))->group(function () {
        Route::post('/admin/admissions/{id}/approve', [ApplicantController::class, 'approve'])->name('admission.approve');
        Route::post('/admin/admissions/{id}/reject', [ApplicantController::class, 'reject'])->name('admission.reject');
    });
});

// ==========================================
// AUTHENTICATION SYSTEM
// ==========================================
require __DIR__.'/auth.php';