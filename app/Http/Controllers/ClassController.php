<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClassController extends Controller
{
    public function index()
    {
        $classes = SchoolClass::orderBy('name')->withCount('teachers')->get()
            ->map(function ($c) {
                $c->student_count = Student::where('class_arm', $c->name)->count();
                return $c;
            });

        return view('classes.index', compact('classes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255', Rule::unique('classes', 'name')],
            'level'   => 'nullable|string|max:50',
            'section' => 'nullable|string|max:50',
        ]);

        SchoolClass::create([
            'name'    => $data['name'],
            'level'   => $data['level'] ?? (Str::startsWith($data['name'], 'SSS') ? 'SSS' : (Str::startsWith($data['name'], 'JSS') ? 'JSS' : null)),
            'section' => $data['section'] ?? Str::substr($data['name'], -1),
            'active'  => true,
        ]);

        return back()->with('success', "Class {$data['name']} created.");
    }

    public function toggle(SchoolClass $schoolClass)
    {
        $schoolClass->update(['active' => !$schoolClass->active]);

        return back()->with('success', "{$schoolClass->name} is now ".($schoolClass->active ? 'active' : 'inactive').'.');
    }
}
