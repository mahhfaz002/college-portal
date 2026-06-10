<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Program;

class LandingPageController extends Controller
{
    public function index()
    {
        // Public page (no tenant context) — show the primary college's prospectus.
        $college = College::where('is_active', true)->orderBy('id')->first();

        $programs = collect();
        if ($college) {
            $programs = Program::withoutGlobalScopes()
                ->where('college_id', $college->id)
                ->with('department')
                ->orderBy('name')
                ->get();
        }

        // Academic calendar / key dates (static for now; editable in a later phase).
        $calendar = [
            ['title' => 'Admission Application Opens',  'date' => '1 July 2026'],
            ['title' => 'Application Deadline',         'date' => '30 September 2026'],
            ['title' => 'Entrance Screening',           'date' => '10 October 2026'],
            ['title' => 'Admission List Released',       'date' => '24 October 2026'],
            ['title' => 'Registration & Resumption',     'date' => '10 November 2026'],
            ['title' => 'First Semester Lectures Begin', 'date' => '17 November 2026'],
        ];

        // Message from the Provost.
        $provost = [
            'name'    => 'Prof. (Mrs.) A. Mahhfaz',
            'title'   => 'Provost',
            'message' => "On behalf of the management, staff and students, I warmly welcome you to MAHHFAZ "
                . "College of Health Sciences and Technology, Jalingo. Our mission is to train competent, "
                . "compassionate and ethical health professionals who will serve Taraba State, Nigeria and "
                . "the world. With modern laboratories, experienced lecturers and a supportive learning "
                . "environment, we are committed to producing graduates of distinction. I invite you to "
                . "join our community and begin a rewarding journey in health sciences and technology.",
        ];

        return view('home', compact('college', 'programs', 'calendar', 'provost'));
    }
}
