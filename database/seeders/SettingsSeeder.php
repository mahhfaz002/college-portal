<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // Branding
            'school_name'        => ['MAHHFAZ School System', 'branding'],
            'school_tagline'     => ['Knowledge • Integrity • Excellence', 'branding'],
            'school_logo'        => [null, 'branding'],
            'primary_color'      => ['#2563eb', 'branding'],
            'school_address'     => ['123 School Road, Jalingo, Taraba', 'branding'],
            'school_phone'       => ['+234 800 000 0000', 'branding'],
            'school_email'       => ['info@mahhfaz.edu', 'branding'],
            'staff_email_domain' => ['mahhfaz.edu', 'branding'],

            // Academic
            'currency_symbol'    => ['₦', 'academic'],
            'current_term'       => ['First Term', 'academic'],
            'current_session'    => ['2025/2026', 'academic'],
            'ca_max_score'       => ['40', 'academic'],
            'exam_max_score'     => ['60', 'academic'],
            // Grading scheme: JSON list of {min, grade, remark}
            'grading_scheme'     => [json_encode([
                ['min' => 70, 'grade' => 'A', 'remark' => 'Excellent'],
                ['min' => 60, 'grade' => 'B', 'remark' => 'Very Good'],
                ['min' => 50, 'grade' => 'C', 'remark' => 'Good'],
                ['min' => 45, 'grade' => 'D', 'remark' => 'Pass'],
                ['min' => 40, 'grade' => 'E', 'remark' => 'Weak Pass'],
                ['min' => 0,  'grade' => 'F', 'remark' => 'Fail'],
            ]), 'academic'],
        ];

        foreach ($defaults as $key => [$value, $group]) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        }
    }
}
