<?php

namespace App\Http\Controllers;

use App\Models\AdmittedRecord;
use App\Models\College;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Super-admin upload of admitted-student records from a CSV, per college.
 * Expected columns (header row, order-independent): full name, registration
 * number, department, level. These records gate student self-registration.
 */
class AdmissionRecordController extends Controller
{
    public function index()
    {
        $colleges = College::orderBy('name')->get();
        $counts = AdmittedRecord::withoutGlobalScopes()
            ->selectRaw('college_id, COUNT(*) total, SUM(CASE WHEN claimed_at IS NULL THEN 0 ELSE 1 END) claimed')
            ->groupBy('college_id')->get()->keyBy('college_id');

        return view('platform.admitted_records', compact('colleges', 'counts'));
    }

    public function upload(Request $request)
    {
        $data = $request->validate([
            'college_id' => 'required|exists:colleges,id',
            'csv'        => 'required|file|mimes:csv,txt|max:4096',
        ]);

        $rows = array_map('str_getcsv', file($request->file('csv')->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        if (empty($rows)) {
            return back()->with('error', 'The CSV file is empty.');
        }

        // Map the header row to our fields (order-independent, fuzzy names).
        $header = array_map(fn ($h) => preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $h))), array_shift($rows));
        $col = function (array $names) use ($header) {
            foreach ($names as $n) {
                $i = array_search($n, $header, true);
                if ($i !== false) {
                    return $i;
                }
            }
            return null;
        };

        $iName  = $col(['fullname', 'name', 'studentname']);
        $iReg   = $col(['registrationnumber', 'regnumber', 'regno', 'registration', 'matricnumber']);
        $iDept  = $col(['department', 'dept']);
        $iLevel = $col(['level']);

        if ($iName === null || $iReg === null) {
            return back()->with('error', 'CSV must include at least "Full Name" and "Registration Number" columns.');
        }

        $created = 0; $updated = 0; $skipped = 0;
        foreach ($rows as $row) {
            $name = trim($row[$iName] ?? '');
            $reg  = trim($row[$iReg] ?? '');
            if ($name === '' || $reg === '') {
                $skipped++;
                continue;
            }

            $record = AdmittedRecord::withoutGlobalScopes()->updateOrCreate(
                ['college_id' => (int) $data['college_id'], 'registration_number' => $reg],
                [
                    'full_name'  => $name,
                    'department' => $iDept !== null ? trim($row[$iDept] ?? '') : null,
                    'level'      => $iLevel !== null ? trim($row[$iLevel] ?? '') : null,
                ]
            );
            $record->wasRecentlyCreated ? $created++ : $updated++;
        }

        return back()->with('success', "Import complete — {$created} added, {$updated} updated".($skipped ? ", {$skipped} skipped (missing name/reg no.)" : '').'.');
    }

    /**
     * Per-college list of uploaded students, with search and onboarding status.
     * Reachable from each college's "Manage College" page.
     */
    public function students(Request $request, College $college)
    {
        $term = trim((string) $request->query('q', ''));

        $records = AdmittedRecord::withoutGlobalScopes()
            ->with('claimer:id,platform_fee_paid')
            ->where('college_id', $college->id)
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.$term.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('full_name', 'like', $like)
                      ->orWhere('registration_number', 'like', $like)
                      ->orWhere('department', 'like', $like);
                });
            })
            ->orderBy('full_name')
            ->paginate(50)
            ->withQueryString();

        return view('platform.college_students', compact('college', 'records', 'term'));
    }

    /**
     * Fix a single uploaded record (e.g. a misspelt name or wrong reg number)
     * after import. Scoped to the college that owns the record.
     */
    public function updateRecord(Request $request, College $college, AdmittedRecord $record)
    {
        abort_unless((int) $record->college_id === (int) $college->id, 404);

        $data = $request->validate([
            'full_name'           => 'required|string|max:255',
            'registration_number' => [
                'required', 'string', 'max:100',
                Rule::unique('admitted_records', 'registration_number')
                    ->where(fn ($q) => $q->where('college_id', $college->id))
                    ->ignore($record->id),
            ],
            'department'          => 'nullable|string|max:255',
            'level'               => 'nullable|string|max:50',
        ]);

        $record->forceFill([
            'full_name'           => $data['full_name'],
            'registration_number' => $data['registration_number'],
            'department'          => $data['department'] ?? null,
            'level'               => $data['level'] ?? null,
        ])->save();

        return back()->with('success', "Updated record for {$record->full_name}.");
    }
}
