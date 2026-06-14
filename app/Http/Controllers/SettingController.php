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
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'school_name'        => 'required|string|max:255',
            'school_tagline'     => 'nullable|string|max:255',
            'primary_color'      => 'nullable|string|max:20',
            'school_address'     => 'nullable|string|max:255',
            'school_phone'       => 'nullable|string|max:50',
            'school_email'       => 'nullable|email|max:255',
            'staff_email_domain' => 'nullable|string|max:255',
            'currency_symbol'    => 'nullable|string|max:5',
            'current_term'       => 'nullable|string|max:50',
            'current_session'    => 'nullable|string|max:20',
            'ca_max_score'       => 'nullable|integer|min:0|max:100',
            'exam_max_score'     => 'nullable|integer|min:0|max:100',
            'logo'               => 'nullable|image|max:2048',
            'grades'             => 'nullable|array',
        ]);

        foreach ($data as $key => $value) {
            if (in_array($key, ['logo', 'grades'])) {
                continue;
            }
            Setting::set($key, $value);
        }

        // Logo upload
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('branding');
            Setting::set('school_logo', $path, 'branding');
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
