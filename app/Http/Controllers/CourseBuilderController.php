<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Program;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Academic Secretary — batch course (subject) creation.
 *
 * Cascading selection: programme type (UG/DIP/CERT) -> department -> course of
 * study -> level. The secretary then adds many courses (title, code, unit) at
 * once and submits them for that level. Re-selecting a level that already has
 * courses loads them for editing/deletion.
 */
class CourseBuilderController extends Controller
{
    public function index()
    {
        // All courses of study with the data the cascading UI needs.
        $programs = Program::with('department')->orderBy('name')->get()->map(fn ($p) => [
            'id'        => $p->id,
            'name'      => $p->name,
            'type'      => $p->program_type ?? 'UG',
            'levels'    => (int) ($p->levels ?: 1),
            'dept_id'   => $p->department_id,
            'dept_name' => $p->department->name ?? '',
        ]);

        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('courses.builder', compact('programs', 'departments'));
    }

    /** JSON: existing courses for a program + level. */
    public function list(Request $request)
    {
        $data = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'level'      => 'required|string',
        ]);

        $courses = Subject::where('program_id', $data['program_id'])
            ->where('level', $data['level'])
            ->orderBy('course_code')
            ->get(['id', 'name', 'course_code', 'course_unit']);

        return response()->json(['courses' => $courses]);
    }

    /** Replace the course set for a program + level with the submitted rows. */
    public function save(Request $request)
    {
        $data = $request->validate([
            'program_id'        => 'required|exists:programs,id',
            'level'             => 'required|string|max:20',
            'courses'           => 'required|array|min:1',
            'courses.*.name'    => 'required|string|max:200',
            'courses.*.course_code' => 'required|string|max:30',
            'courses.*.course_unit' => 'required|integer|min:1|max:12',
        ]);

        $program = Program::findOrFail($data['program_id']);

        DB::transaction(function () use ($data, $program) {
            // Re-sync this level's courses.
            Subject::where('program_id', $program->id)->where('level', $data['level'])->delete();

            foreach ($data['courses'] as $c) {
                Subject::create([
                    'college_id'    => $program->college_id,
                    'department_id' => $program->department_id,
                    'program_id'    => $program->id,
                    'level'         => $data['level'],
                    'name'          => $c['name'],
                    'course_code'   => strtoupper($c['course_code']),
                    'course_unit'   => $c['course_unit'],
                ]);
            }
        });

        return back()->with('success', count($data['courses']).' course(s) saved for level '.$data['level'].'.');
    }
}
