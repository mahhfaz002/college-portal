<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all();
        // The MIS edits its OWN college's provost block + key dates (per-tenant).
        $college  = current_college();
        return view('settings.index', compact('settings', 'college'));
    }

    public function update(Request $request)
    {
        // Branding (name, logo, colours, contact) is set by the super-admin on the
        // College record — it is NOT editable here. The MIS edits academic
        // settings plus its OWN college's provost block and homepage key dates.
        $data = $request->validate([
            'currency_symbol'    => 'nullable|string|max:5',
            'current_term'       => 'nullable|string|max:50',
            'current_session'    => 'nullable|string|max:20',
            'ca_max_score'       => 'nullable|integer|min:0|max:100',
            'exam_max_score'     => 'nullable|integer|min:0|max:100',
            'grades'             => 'nullable|array',
            // Per-college provost block + key dates (written to the College record).
            'provost_name'       => 'nullable|string|max:255',
            'provost_title'      => 'nullable|string|max:255',
            'provost_message'    => 'nullable|string|max:2000',
            'provost_photo'      => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'key_dates'          => 'nullable|array',
            'key_dates.*.title'  => 'nullable|string|max:255',
            'key_dates.*.date'   => 'nullable|string|max:100',
        ]);

        foreach (['currency_symbol', 'current_term', 'current_session', 'ca_max_score', 'exam_max_score'] as $key) {
            if (array_key_exists($key, $data)) {
                Setting::set($key, $data[$key]);
            }
        }

        // Per-college provost block + key dates → the MIS officer's OWN college.
        if ($college = current_college()) {
            $college->provost_name    = $data['provost_name'] ?? $college->provost_name;
            $college->provost_title   = $data['provost_title'] ?? $college->provost_title;
            $college->provost_message = $data['provost_message'] ?? $college->provost_message;

            if ($request->hasFile('provost_photo')) {
                $file = $request->file('provost_photo');
                $college->provost_photo = 'data:'.$file->getMimeType().';base64,'
                    .base64_encode(file_get_contents($file->getRealPath()));
            }

            // Keep only fully-filled key-date rows (title + date).
            $college->key_dates = collect($request->input('key_dates', []))
                ->map(fn ($r) => ['title' => trim($r['title'] ?? ''), 'date' => trim($r['date'] ?? '')])
                ->filter(fn ($r) => $r['title'] !== '' && $r['date'] !== '')
                ->values()->all();

            $college->save();
        }

        // Grading scheme (parallel arrays from the form)
        if ($request->filled('grades')) {
            $scheme = collect($request->input('grades'))
                ->filter(fn ($row) => isset($row['grade']) && $row['grade'] !== '')
                ->map(fn ($row) => [
                    'min'    => (int) ($row['min'] ?? 0),
                    'grade'  => $row['grade'],
                    'remark' => $row['remark'] ?? '',
                ])
                ->sortByDesc('min')
                ->values()
                ->all();
            Setting::set('grading_scheme', json_encode($scheme), 'academic');
        }

        ActivityLog::record('Updated college settings', 'settings.update');

        return back()->with('success', 'College settings updated successfully.');
    }
}
