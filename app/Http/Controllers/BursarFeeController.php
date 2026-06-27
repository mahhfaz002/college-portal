<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * Bursar-managed fee settings (Fees menu). Currently the change-of-course fee,
 * which the student dashboard reads when a student applies for a course change.
 */
class BursarFeeController extends Controller
{
    public function index()
    {
        return view('fees.settings', [
            'changeOfCourseFee' => setting('change_of_course_fee', \App\Models\ChangeOfCourseRequest::FEE),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'change_of_course_fee' => 'required|numeric|min:0',
        ]);

        Setting::set('change_of_course_fee', $data['change_of_course_fee'], 'fees');

        ActivityLog::record("Set change-of-course fee to {$data['change_of_course_fee']}", 'fees.coc');

        return back()->with('success', 'Change-of-course fee updated. It now applies to student requests.');
    }
}
