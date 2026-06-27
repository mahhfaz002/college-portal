<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Setting;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'semester_status'      => 'active',
            'break_start'          => null,
            'break_end'            => null,
            'next_semester_start'  => null,
            'next_session_start'   => null,
            'change_of_course_fee' => (string) \App\Models\ChangeOfCourseRequest::FEE,
        ];

        foreach ($defaults as $key => $value) {
            if (!Setting::where('key', $key)->exists()) {
                Setting::set($key, $value, 'academic');
            }
        }
    }

    public function down(): void
    {
        Setting::whereIn('key', [
            'semester_status', 'break_start', 'break_end',
            'next_semester_start', 'next_session_start', 'change_of_course_fee',
        ])->delete();
    }
};
