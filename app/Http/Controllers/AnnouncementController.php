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

        // A student only sees notices for everyone, for students, or for their class.
        $classArm = $role === 'student'
            ? optional(Student::where('email', $user->email)->first())->class_arm
            : null;

        $announcements = Announcement::visibleTo($role, $classArm)
            ->with('author')
            ->latest()
            ->paginate(15);

        $canManage = in_array($role, $this->managers, true);
        $classes = $canManage ? SchoolClass::orderBy('name')->pluck('name') : collect();

        return view('announcements.index', compact('announcements', 'canManage', 'classes'));
    }

    public function store(Request $request)
    {
        abort_unless(in_array(auth()->user()->role, $this->managers, true), 403);

        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'body'         => 'required|string',
            'audience'     => ['required', Rule::in(['all', 'staff', 'students', 'both', 'class'])],
            'target_class' => 'nullable|required_if:audience,class|string',
        ]);
        $data['user_id'] = auth()->id();
        if ($data['audience'] !== 'class') {
            $data['target_class'] = null;
        }

        Announcement::create($data);
        ActivityLog::record('Posted announcement: ' . $data['title'], 'announcement.create');

        return back()->with('success', 'Announcement posted.');
    }

    public function destroy(Announcement $announcement)
    {
        abort_unless(in_array(auth()->user()->role, $this->managers, true), 403);
        $announcement->delete();

        return back()->with('success', 'Announcement deleted.');
    }
}
