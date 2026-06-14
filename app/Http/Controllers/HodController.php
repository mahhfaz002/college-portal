<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use App\Support\Usernames;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Head of Department workspace: view the department's students (read-only) and
 * register resource persons (lecturers) for the department.
 */
class HodController extends Controller
{
    private function deptId(): ?int
    {
        return auth()->user()->department_id;
    }

    /**
     * Read-only courses in the HOD's department, filterable by course of study,
     * level and semester. The HOD does not create or edit courses.
     */
    public function courses()
    {
        $deptId     = $this->deptId();
        $department = Department::find($deptId);

        $programs = Program::where('department_id', $deptId)->orderBy('name')->get()->map(fn ($p) => [
            'id'     => $p->id,
            'name'   => $p->name,
            'levels' => (int) ($p->levels ?: 1),
        ])->values();

        $subjects = \App\Models\Subject::where('department_id', $deptId)
            ->whereNotNull('program_id')
            ->orderBy('level')->orderBy('course_code')
            ->get(['id', 'name', 'course_code', 'course_unit', 'program_id', 'level', 'semester'])
            ->map(fn ($s) => [
                'id'          => $s->id,
                'name'        => $s->name,
                'course_code' => $s->course_code,
                'course_unit' => $s->course_unit,
                'program_id'  => $s->program_id,
                'level'       => (string) $s->level,
                'semester'    => $s->semester,
            ])->values();

        return view('hod.courses', [
            'department' => $department,
            'programs'   => $programs,
            'subjects'   => $subjects,
            'semesters'  => \App\Support\Semesters::ALL,
        ]);
    }

    /** Exam's course must belong to the HOD's department. */
    private function authorizeExamDept(\App\Models\Exam $exam): void
    {
        $subject = \App\Models\Subject::find($exam->subject_id);
        abort_unless($subject && (int) $subject->department_id === (int) $this->deptId(), 403,
            'This exam is not in your department.');
    }

    /**
     * REVIEW queue: question sets submitted by lecturers for courses in this
     * department. Review-only (no editing); ?open={exam} opens the read-only popup.
     */
    public function examReviews()
    {
        $deptId     = $this->deptId();
        $programIds = Program::where('department_id', $deptId)->pluck('id');
        $subjectIds = \App\Models\Subject::whereIn('program_id', $programIds)->pluck('id');
        $cycle      = \App\Models\ExamCycle::active();

        $exams = \App\Models\Exam::whereIn('subject_id', $subjectIds)
            ->where('status', 'submitted')
            ->when($cycle, fn ($q) => $q->where('exam_cycle_id', $cycle->id))
            ->with('subject.program')
            ->withCount([
                'questions as objective_count' => fn ($q) => $q->where('type', 'objective'),
                'questions as theory_count' => fn ($q) => $q->where('type', 'theory'),
            ])->get();

        $openExam = null;
        if (request('open')) {
            $openExam = \App\Models\Exam::with(['questions' => fn ($q) => $q->orderBy('type')->orderBy('theory_number')->orderBy('id'), 'subject.program'])
                ->find(request('open'));
            // Only reviewable while still submitted and in this department.
            if ($openExam) {
                $this->authorizeExamDept($openExam);
                if ($openExam->status !== 'submitted') {
                    $openExam = null;
                }
            }
        }

        $department = Department::find($deptId);

        return view('hod.exam_reviews', compact('exams', 'openExam', 'department', 'cycle'));
    }

    /** Approve a submitted question set → forwards to the Exam Officer; HOD loses access. */
    public function approveExam(\App\Models\Exam $exam)
    {
        $this->authorizeExamDept($exam);
        abort_unless($exam->status === 'submitted', 403, 'Only submitted question sets can be approved.');

        $exam->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'hod_feedback'=> null,
        ]);

        return redirect()->route('hod.exam-reviews')->with('success', 'Questions approved and forwarded to the Exam Officer.');
    }

    /** Query/reject a submitted set back to the lecturer with reasons. */
    public function queryExam(Request $request, \App\Models\Exam $exam)
    {
        $this->authorizeExamDept($exam);
        abort_unless($exam->status === 'submitted', 403, 'Only submitted question sets can be queried.');

        $data = $request->validate([
            'hod_feedback' => 'required|string|max:2000',
        ]);

        $exam->update([
            'status'       => 'hod_returned',
            'submitted_at' => null,          // unlock for lecturer editing
            'hod_feedback' => $data['hod_feedback'],
            'reviewed_by'  => auth()->id(),
            'reviewed_at'  => now(),
        ]);

        return redirect()->route('hod.exam-reviews')->with('success', 'Questions returned to the lecturer with your query.');
    }

    /** Students in the HOD's department, with registration status. */
    public function students()
    {
        $deptId = $this->deptId();
        $programIds = Program::where('department_id', $deptId)->pluck('id');

        $students = Student::whereIn('program_id', $programIds)
            ->with('program')
            ->orderBy('full_name')
            ->paginate(50)->withQueryString();

        $department = Department::find($deptId);

        return view('hod.students', compact('students', 'department'));
    }

    /** Full student detail incl. uploaded documents (dept-scoped). */
    public function showStudent(Student $student)
    {
        $this->authorizeDept($student->department_id);
        $documents = StudentDocument::where('student_id', $student->id)->get();
        $student->load('program', 'department');

        return view('hod.student_show', compact('student', 'documents'));
    }

    /** Resource persons (lecturers) in the department + create form. */
    public function resourcePersons()
    {
        $deptId = $this->deptId();
        $lecturers = User::where('role', 'lecturer')
            ->where('department_id', $deptId)
            ->orderBy('name')->get();
        $department = Department::find($deptId);

        return view('hod.resource_persons', compact('lecturers', 'department'));
    }

    public function storeResourcePerson(Request $request)
    {
        $deptId = $this->deptId();

        $data = $request->validate([
            'first_name'      => 'required|string|max:100',
            'other_name'      => 'nullable|string|max:100',
            'surname'         => 'required|string|max:100',
            'address'         => 'nullable|string|max:255',
            'phone'           => 'required|string|max:50',
            'email'           => 'required|email|max:255|unique:users,email',
            'qualification'   => 'required|string|max:150',
            'university'      => 'required|string|max:200',
            'class_of_degree' => 'nullable|string|max:100',
            'temp_password'   => 'required|string|min:6',
            'passport'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $username = Usernames::generate($data['first_name'], $data['other_name'] ?? null, $data['surname']);
        $fullName = trim($data['first_name'].' '.($data['other_name'] ?? '').' '.$data['surname']);

        $passport = null;
        if ($request->hasFile('passport')) {
            $f = $request->file('passport');
            $passport = 'data:'.$f->getMimeType().';base64,'.base64_encode(file_get_contents($f->getRealPath()));
        }

        $user = User::create([
            'first_name'      => $data['first_name'],
            'surname'         => $data['surname'],
            'name'            => $fullName,
            'email'           => $data['email'],
            'username'        => $username,
            'phone'           => $data['phone'],
            'address'         => $data['address'] ?? null,
            'password'        => Hash::make($data['temp_password']),
            'role'            => 'lecturer',
            'college_id'      => auth()->user()->college_id,
            'department_id'   => $deptId,
            'department'      => optional(Department::find($deptId))->name,
            'qualification'   => $data['qualification'],
            'university'      => $data['university'],
            'class_of_degree' => $data['class_of_degree'] ?? null,
            'passport'        => $passport,
            'status'          => 'active',
            'must_change_password' => true,   // prompted to change on first login
            'platform_fee_paid'    => true,
        ]);

        ActivityLog::record("Registered resource person {$fullName} ({$username})", 'hod.resource_person');

        return redirect()->route('hod.resource-persons')->with('success',
            "Resource person created. Username: {$username} · Email: {$data['email']} · Temp password: {$data['temp_password']} (they will be prompted to change it).");
    }

    private function authorizeDept(?int $deptId): void
    {
        abort_unless($deptId !== null && $deptId === $this->deptId(), 403, 'Not in your department.');
    }

    /* --------------------------------------------------------------------
     | Department grading scheme (score → grade), applied to all dept students
     |--------------------------------------------------------------------*/

    public function grading()
    {
        $deptId = $this->deptId();
        $department = Department::find($deptId);
        $bands = \App\Models\GradingScheme::where('department_id', $deptId)
            ->orderByDesc('min_score')->get();

        return view('hod.grading', compact('department', 'bands'));
    }

    public function saveGrading(Request $request)
    {
        $deptId = $this->deptId();

        $data = $request->validate([
            'grade'     => 'required|array|min:1',
            'grade.*'   => 'nullable|string|max:10',
            'min_score' => 'required|array',
            'max_score' => 'required|array',
            'remark'    => 'nullable|array',
        ]);

        \App\Models\GradingScheme::where('department_id', $deptId)->delete();

        foreach ($data['grade'] as $i => $grade) {
            if ($grade === null || $grade === '') {
                continue;
            }
            \App\Models\GradingScheme::create([
                'college_id'    => auth()->user()->college_id,
                'department_id' => $deptId,
                'grade'         => $grade,
                'min_score'     => (int) ($data['min_score'][$i] ?? 0),
                'max_score'     => (int) ($data['max_score'][$i] ?? 0),
                'remark'        => $data['remark'][$i] ?? null,
                'sort'          => $i,
            ]);
        }

        return back()->with('success', 'Department grading scheme saved. It applies to all students in your department.');
    }
}
