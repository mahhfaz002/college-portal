<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Academic Secretary assigns courses (subjects) to academic lecturers.
 * Reuses the existing subject_teacher pivot.
 */
class CourseAssignmentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'user_id'    => 'required|exists:users,id',
        ]);

        $lecturer = User::whereIn('role', ['lecturer', 'hod', 'assistant_hod'])->findOrFail($data['user_id']);
        $course = Subject::findOrFail($data['subject_id']);

        // syncWithoutDetaching avoids duplicate pivot rows.
        $course->teachers()->syncWithoutDetaching([$lecturer->id]);

        return back()->with('success', "Assigned “{$course->name}” to {$lecturer->name}.");
    }

    /** Assign several courses to one lecturer at once (batch). */
    public function storeBatch(Request $request)
    {
        $data = $request->validate([
            'subject_ids'   => 'required|array|min:1',
            'subject_ids.*' => 'integer|exists:subjects,id',
            'user_id'       => 'required|exists:users,id',
        ]);

        $lecturer = User::whereIn('role', ['lecturer', 'hod', 'assistant_hod'])->findOrFail($data['user_id']);

        foreach ($data['subject_ids'] as $subjectId) {
            Subject::findOrFail($subjectId)->teachers()->syncWithoutDetaching([$lecturer->id]);
        }

        return back()->with('success', count($data['subject_ids'])." course(s) assigned to {$lecturer->name}.");
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'user_id'    => 'required|exists:users,id',
        ]);

        Subject::findOrFail($data['subject_id'])->teachers()->detach($data['user_id']);

        return back()->with('success', 'Course assignment removed.');
    }
}
