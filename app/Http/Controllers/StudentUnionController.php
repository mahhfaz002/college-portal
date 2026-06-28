<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\StudentUnion;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Student unions, groups & organisations managed by Student Affairs:
 * the SUG, departmental associations, cultural / LGA / state associations, etc.
 * Each carries its current leadership, whose tenure runs one year from the
 * registration date.
 */
class StudentUnionController extends Controller
{
    public function index()
    {
        $unions = StudentUnion::with('leaders')->latest()->get();

        return view('affairs.unions.index', compact('unions'));
    }

    public function create()
    {
        return view('affairs.unions.form', ['union' => new StudentUnion(['status' => 'active']), 'leaders' => []]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $union = StudentUnion::create([
            'college_id'       => current_college_id(),
            'name'             => $data['name'],
            'acronym'          => $data['acronym'] ?? null,
            'year_established' => $data['year_established'] ?? null,
            'constituents'     => $data['constituents'] ?? null,
            'members_count'    => $data['members_count'] ?? 0,
            'status'           => 'active',
            'created_by'       => auth()->id(),
        ]);

        $this->syncLeaders($union, $data['leaders'] ?? []);
        ActivityLog::record("Registered student union '{$union->name}'", 'union.create');

        return redirect()->route('affairs.unions.index')->with('success', "“{$union->name}” registered.");
    }

    public function edit(StudentUnion $union)
    {
        $union->load('leaders');

        return view('affairs.unions.form', ['union' => $union, 'leaders' => $union->leaders]);
    }

    public function update(Request $request, StudentUnion $union)
    {
        $data = $this->validated($request);

        $union->update([
            'name'             => $data['name'],
            'acronym'          => $data['acronym'] ?? null,
            'year_established' => $data['year_established'] ?? null,
            'constituents'     => $data['constituents'] ?? null,
            'members_count'    => $data['members_count'] ?? 0,
        ]);

        $this->syncLeaders($union, $data['leaders'] ?? []);
        ActivityLog::record("Updated student union '{$union->name}'", 'union.update');

        return redirect()->route('affairs.unions.index')->with('success', "“{$union->name}” updated.");
    }

    /** Suspend / reactivate a union. */
    public function suspend(StudentUnion $union)
    {
        $union->update(['status' => $union->isSuspended() ? 'active' : 'suspended']);

        return back()->with('success', $union->isSuspended()
            ? "“{$union->name}” suspended."
            : "“{$union->name}” reactivated.");
    }

    public function destroy(StudentUnion $union)
    {
        $name = $union->name;
        $union->delete(); // leaders cascade

        return back()->with('success', "“{$name}” deleted.");
    }

    /* -------------------------------------------------------------- */

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'                 => 'required|string|max:200',
            'acronym'              => 'nullable|string|max:30',
            'year_established'     => 'nullable|integer|min:1900|max:'.date('Y'),
            'constituents'         => 'nullable|string|max:2000',
            'members_count'        => 'nullable|integer|min:0',
            'leaders'              => 'array',
            'leaders.*.name'       => 'nullable|string|max:150',
            'leaders.*.department' => 'nullable|string|max:150',
            'leaders.*.course_of_study' => 'nullable|string|max:150',
            'leaders.*.level'      => 'nullable|string|max:20',
            'leaders.*.position'   => 'nullable|string|max:100',
            'leaders.*.tenure_start' => 'nullable|date',
        ]);
    }

    /** Replace the union's leadership; tenure runs one year from its start date. */
    private function syncLeaders(StudentUnion $union, array $leaders): void
    {
        $union->leaders()->delete();

        foreach ($leaders as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $position = trim((string) ($row['position'] ?? ''));
            if ($name === '' || $position === '') {
                continue; // skip blank rows
            }
            $start = !empty($row['tenure_start']) ? Carbon::parse($row['tenure_start']) : now();

            $union->leaders()->create([
                'name'            => $name,
                'department'      => $row['department'] ?? null,
                'course_of_study' => $row['course_of_study'] ?? null,
                'level'           => $row['level'] ?? null,
                'position'        => $position,
                'tenure_start'    => $start->toDateString(),
                'tenure_end'      => $start->copy()->addYear()->toDateString(),
            ]);
        }
    }
}
