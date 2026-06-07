<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Payment;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Subjects
        $subjects = collect(['Mathematics', 'English Language', 'Basic Science', 'Social Studies', 'ICT'])
            ->map(fn ($name) => Subject::firstOrCreate(['name' => $name]));

        // A student with a linked login (so the student dashboard shows data)
        $demoStudentEmail = 'student@mahhfaz.edu';
        $names = [
            ['Aisha Bello', 'JSS1A'], ['Chinedu Okafor', 'JSS1A'], ['Fatima Sani', 'JSS1A'],
            ['Emeka Obi', 'JSS2A'], ['Grace Adeyemi', 'JSS2A'], ['Yusuf Musa', 'JSS2A'],
        ];

        foreach ($names as $i => [$fullName, $class]) {
            $email = $i === 0 ? $demoStudentEmail : strtolower(str_replace(' ', '.', $fullName)) . '@student.mahhfaz.edu';
            $student = Student::firstOrCreate(
                ['admission_number' => 'MAH/2025/' . str_pad($i + 1, 3, '0', STR_PAD_LEFT)],
                [
                    'full_name' => $fullName,
                    'email' => $email,
                    'class_arm' => $class,
                    'parent_phone' => '+234 80' . rand(10000000, 99999999),
                    'fees_balance' => [0, 25000, 50000][$i % 3],
                    'blood_group' => ['O+', 'A+', 'B+'][$i % 3],
                ]
            );

            // Scores across subjects
            foreach ($subjects as $subject) {
                Score::firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'subject_id' => $subject->id,
                        'term' => setting('current_term', 'First Term'),
                        'session' => setting('current_session', '2025/2026'),
                    ],
                    ['ca_score' => rand(20, 38), 'exam_score' => rand(30, 58)]
                );
            }

            // A payment
            if ($student->fees_balance < 50000) {
                Payment::firstOrCreate(
                    ['student_id' => $student->id, 'amount' => 50000],
                    ['payment_method' => 'Cash', 'description' => 'Term fees part-payment']
                );
            }

            // Attendance for today
            Attendance::firstOrCreate(
                ['student_id' => $student->id, 'attendance_date' => date('Y-m-d')],
                ['status' => $i % 4 === 0 ? 'absent' : 'present']
            );
        }

        // A login for the demo student
        User::updateOrCreate(
            ['email' => $demoStudentEmail],
            [
                'name' => 'Aisha Bello',
                'password' => Hash::make('password'),
                'role' => 'student',
                'must_change_password' => false,
            ]
        );

        // A couple of pending applications for the registrar to review.
        \App\Models\Applicant::firstOrCreate(
            ['parent_email' => 'newparent@example.com'],
            [
                'full_name' => 'Zainab Ibrahim', 'date_of_birth' => '2014-05-10', 'gender' => 'Female',
                'parent_name' => 'Mr. Ibrahim', 'parent_phone' => '+234 803 222 1111',
                'desired_class' => 'JSS1A', 'status' => 'pending',
            ]
        );

        // A welcome announcement
        Announcement::firstOrCreate(
            ['title' => 'Welcome to the new term'],
            [
                'user_id' => User::where('role', 'proprietor')->value('id'),
                'body' => 'Lessons resume Monday. Please ensure all fees are up to date.',
                'audience' => 'all',
                'is_published' => true,
            ]
        );
    }
}
