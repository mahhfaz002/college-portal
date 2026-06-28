<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    /**
     * Roles allowed to create/delete announcements.
     */
    private array $managers = ['proprietor', 'registrar', 'mis', 'student_affairs'];

    public function index()
    {
        $user = auth()->user();
        $role = $user->role ?? 'student';

        // A student only sees notices for everyone, for students (matching their
        // department / course / level targets), or for their class.
        $student = $role === 'student' ? Student::where('email', $user->email)->first() : null;
        $classArm = optional($student)->class_arm;

        $announcements = Announcement::visibleTo($role, $classArm, $student)
            ->with('author')
            ->latest()
            ->paginate(15);

        $canManage = in_array($role, $this->managers, true);
        $classes = $canManage ? SchoolClass::orderBy('name')->pluck('name') : collect();

        // Student-targeting options (Student Affairs filters its notices by these).
        $departments = $canManage ? \App\Models\Department::orderBy('name')->get(['id', 'name']) : collect();
        $programs    = $canManage ? \App\Models\Program::orderBy('name')->get(['id', 'name', 'department_id']) : collect();
        $levels      = ['100', '200', '300', '400', '500', '600'];

        return view('announcements.index', compact('announcements', 'canManage', 'classes', 'role', 'departments', 'programs', 'levels'));
    }

    public function store(Request $request)
    {
        abort_unless(in_array(auth()->user()->role, $this->managers, true), 403);

        // Student Affairs announcements are always student-targeted; their form
        // omits the audience picker, so set it before validation.
        if (auth()->user()->role === 'student_affairs') {
            $request->merge(['audience' => 'students']);
        }

        $data = $request->validate([
            'title'                => 'required|string|max:255',
            'body'                 => 'required|string',
            'audience'             => ['required', Rule::in(['all', 'staff', 'students', 'both', 'class'])],
            'target_class'         => 'nullable|required_if:audience,class|string',
            'target_department_id' => 'nullable|exists:departments,id',
            'target_program_id'    => 'nullable|exists:programs,id',
            'target_level'         => 'nullable|string|max:20',
        ]);
        $data['user_id'] = auth()->id();

        // Student Affairs announcements ALWAYS go to students only.
        if (auth()->user()->role === 'student_affairs') {
            $data['audience'] = 'students';
        }

        if ($data['audience'] !== 'class') {
            $data['target_class'] = null;
        }
        // Department / course / level targets only make sense for students.
        if (! in_array($data['audience'], ['students', 'all', 'both'], true)) {
            $data['target_department_id'] = $data['target_program_id'] = $data['target_level'] = null;
        }

        Announcement::create($data);
        ActivityLog::record('Posted announcement: ' . $data['title'], 'announcement.create');

        return back()->with('success', 'Announcement posted.');
    }

    public function destroy(Announcement $announcement)
    {
        $user = auth()->user();
        abort_unless(in_array($user->role, $this->managers, true), 403);

        // You may only delete an announcement you created (MIS, the system
        // administrator, may remove any).
        abort_unless(
            $announcement->user_id === $user->id || $user->role === 'mis',
            403,
            'You can only delete announcements you created.'
        );

        $announcement->delete();

        return back()->with('success', 'Announcement deleted.');
    }
}
