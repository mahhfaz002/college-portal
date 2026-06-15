<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Program;

class LandingPageController extends Controller
{
    public function index()
    {
        // STRICT host-based tenancy: resolve ONLY the college that owns this
        // domain (bound by SetCollegeContext). Never fall back to "the first
        // college" — that would leak one institution's branding onto another
        // domain (and onto the super-admin address).
        $college = current_college();

        // Local development convenience ONLY (APP_ENV=local) — never on Cloud.
        if (!$college && app()->isLocal()) {
            $college = College::where('is_active', true)->orderBy('id')->first();
        }

        // No institution maps to this host → it is the platform / super-admin
        // address (or an unconfigured domain). Do NOT resolve any tenant data.
        if (!$college) {
            if (request()->getHost() === config('app.super_admin_domain')) {
                return redirect()->route('login');   // super-admin entry only
            }
            abort(404);   // unknown domain must not resolve to any college
        }

        $programs = Program::withoutGlobalScopes()
            ->where('college_id', $college->id)
            ->with('department')
            ->orderBy('name')
            ->get();

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
