<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Program;

class LandingPageController extends Controller
{
    public function index()
    {
        // Resolve the tenant from the request domain (bound by SetCollegeContext);
        // fall back to the first active college for the shared/platform address.
        $college = current_college()
            ?? College::where('is_active', true)->orderBy('id')->first();

        $programs = collect();
        if ($college) {
            $programs = Program::withoutGlobalScopes()
                ->where('college_id', $college->id)
                ->with('department')
                ->orderBy('name')
                ->get();
        }

        // Academic calendar / key dates (static defaults; per-college editable later).
        $calendar = [
            ['title' => 'Admission Application Opens',  'date' => '1 July 2026'],
            ['title' => 'Application Deadline',         'date' => '30 September 2026'],
            ['title' => 'Entrance Screening',           'date' => '10 October 2026'],
            ['title' => 'Admission List Released',       'date' => '24 October 2026'],
            ['title' => 'Registration & Resumption',     'date' => '10 November 2026'],
            ['title' => 'First Semester Lectures Begin', 'date' => '17 November 2026'],
        ];

        // Provost message comes from the college record (per-tenant branding).
        $provost = [
            'name'    => $college->provost_name ?? 'The Provost',
            'title'   => $college->provost_title ?? 'Provost',
            'message' => $college->provost_message
                ?? 'Welcome to our college. We are committed to producing competent, '
                 . 'compassionate and ethical professionals who will serve their communities and the world.',
        ];

        return view('landing', compact('college', 'programs', 'calendar', 'provost'));
    }
}
