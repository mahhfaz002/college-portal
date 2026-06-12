<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\College;
use App\Models\Department;
use App\Models\Program;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Clean tertiary bootstrap seed (no demo students, no demo staff).
 *
 * Creates the MAHHFAZ college, its academic structure (departments / programs /
 * sample courses), and ONLY the minimal logins needed to operate the system:
 * Proprietor (oversight), Registrar (registers all staff & students) and ICT
 * (support / password resets). All real staff are added later via the Registrar.
 *
 * Sample password for the bootstrap accounts: "password".
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // --- College (tenant) ---
        $mahhfaz = College::updateOrCreate(
            ['name' => 'MAHHFAZ College of Health Sciences and Technology, Jalingo'],
            [
                'acronym' => 'MAHHFAZ',
                'address' => 'Jalingo, Taraba State, Nigeria',
                'phone'   => '+234 800 000 0000',
                'email'   => 'info@mahhfaz.edu.ng',
                'primary_color' => '#1d4ed8',
                'registration_no_format' => '{acronym}/{year}/{type}/{program}/{serial}',
                'is_active' => true,
                'tagline'  => 'Knowledge • Service • Excellence in Health Education',
                'motto'    => 'Training Hands that Heal',
                'established_year' => '2008',
                'about'    => 'A focused health-sciences institution in Jalingo combining academic rigour '
                            . 'with practical, profession-ready training across diploma and certificate programmes.',
                'provost_name'    => 'Prof. (Mrs.) A. Mahhfaz',
                'provost_title'   => 'Provost',
                'provost_message' => 'On behalf of management, staff and students, I warmly welcome you to '
                            . 'MAHHFAZ College of Health Sciences and Technology, Jalingo. We are committed to '
                            . 'producing competent, compassionate and ethical health professionals.',
            ]
        );

        // --- Sample academic structure (departments -> programs -> courses) ---
        $departments = [
            ['Science Laboratory Technology', 'SLT'],
            ['Community Health', 'CHEW'],
            ['Environmental Health Technology', 'EHT'],
            ['Health Information Management', 'HIM'],
            ['Pharmacy Technician', 'PHT'],
        ];

        // Each department is placed in a section: UG / DIP / CERT.
        $deptSections = ['SLT' => 'DIP', 'CHEW' => 'CERT', 'EHT' => 'DIP', 'HIM' => 'DIP', 'PHT' => 'DIP'];

        $deptModels = [];
        foreach ($departments as [$name, $acr]) {
            $deptModels[$acr] = Department::updateOrCreate(
                ['college_id' => $mahhfaz->id, 'name' => $name],
                ['acronym' => $acr, 'section' => $deptSections[$acr] ?? 'DIP']
            );
        }

        // [dept, name, acronym, type(UG/DIP/CERT), levels, app, acc, reg]
        $programs = [
            ['SLT',  'ND Science Laboratory Technology', 'ND-SLT', 'DIP', 4, 5000, 20000, 75000],
            ['SLT',  'HND Science Laboratory Technology', 'HND-SLT', 'UG', 4, 7500, 25000, 95000],
            ['CHEW', 'Community Health Extension Worker', 'CHEW', 'CERT', 6, 5000, 15000, 60000],
            ['EHT',  'ND Environmental Health Technology', 'ND-EHT', 'DIP', 4, 5000, 20000, 70000],
            ['HIM',  'ND Health Information Management', 'ND-HIM', 'DIP', 4, 5000, 20000, 70000],
            ['PHT',  'ND Pharmacy Technician', 'ND-PHT', 'DIP', 4, 5000, 20000, 80000],
        ];

        $progModels = [];
        foreach ($programs as [$deptAcr, $name, $acr, $type, $levels, $app, $acc, $reg]) {
            $progModels[$acr] = Program::updateOrCreate(
                ['college_id' => $mahhfaz->id, 'department_id' => $deptModels[$deptAcr]->id, 'name' => $name],
                ['acronym' => $acr, 'program_type' => $type, 'levels' => $levels,
                 'level_system' => $type, 'duration_years' => max(1, (int) ceil($levels / 2)),
                 'application_fee' => $app, 'acceptance_fee' => $acc, 'registration_fee' => $reg]
            );
        }

        $courses = [
            ['Introduction to Laboratory Science', 'SLT 101', 3, 'SLT', 'ND-SLT'],
            ['General Microbiology', 'SLT 102', 2, 'SLT', 'ND-SLT'],
            ['Clinical Chemistry', 'SLT 201', 3, 'SLT', 'HND-SLT'],
            ['Primary Health Care', 'CHEW 101', 3, 'CHEW', 'CHEW'],
            ['Anatomy & Physiology', 'CHEW 102', 3, 'CHEW', 'CHEW'],
            ['Environmental Sanitation', 'EHT 101', 2, 'EHT', 'ND-EHT'],
            ['Medical Records Practice', 'HIM 101', 2, 'HIM', 'ND-HIM'],
            ['Pharmacology I', 'PHT 101', 3, 'PHT', 'ND-PHT'],
        ];

        foreach ($courses as [$title, $code, $unit, $deptAcr, $progAcr]) {
            Subject::updateOrCreate(
                ['college_id' => $mahhfaz->id, 'name' => $title],
                ['course_code' => $code, 'course_unit' => $unit,
                 'department_id' => $deptModels[$deptAcr]->id, 'program_id' => $progModels[$progAcr]->id]
            );
        }

        // --- Platform super-admin (no college → sees ALL colleges) ---
        User::updateOrCreate(
            ['email' => 'superadmin@mahhfaz.edu.ng'],
            [
                'name'                 => 'Platform Super Admin',
                'password'             => Hash::make('password'),
                'role'                 => 'superadmin',
                'college_id'           => null,
                'platform_fee_paid'    => true,
                'must_change_password' => false,
            ]
        );

        // --- Sample login for every role (password: "password") ---
        // [name, email, role, department acronym|null]
        $users = [
            ['Proprietor (Owner)',     'proprietor@mahhfaz.edu.ng', 'proprietor',        null],
            ['College Registrar',      'registrar@mahhfaz.edu.ng',  'registrar',         null],
            ['MIS Administrator',      'mis@mahhfaz.edu.ng',        'mis',               null],
            ['Admission Officer',      'admissions@mahhfaz.edu.ng', 'admission_officer', null],
            ['College Bursar',         'bursar@mahhfaz.edu.ng',     'bursar',            null],
            ['Exams Officer',          'exams@mahhfaz.edu.ng',      'exam_officer',      null],
            ['HOD — SLT',              'hod@mahhfaz.edu.ng',        'hod',               'SLT'],
            ['Assistant HOD — SLT',    'asst.hod@mahhfaz.edu.ng',   'assistant_hod',     'SLT'],
            ['Academic Secretary',     'acadsec@mahhfaz.edu.ng',    'academic_secretary',null],
            ['Student Affairs Officer','affairs@mahhfaz.edu.ng',    'student_affairs',   null],
            ['College Librarian',      'library@mahhfaz.edu.ng',    'librarian',         null],
            ['Office Secretary',       'office@mahhfaz.edu.ng',     'office_secretary',  null],
            ['Lecturer — SLT',         'lecturer@mahhfaz.edu.ng',   'lecturer',          'SLT'],
        ];

        foreach ($users as [$name, $email, $role, $deptAcr]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name'                 => $name,
                    'password'             => Hash::make('password'),
                    'role'                 => $role,
                    'college_id'           => $mahhfaz->id,
                    'department_id'        => $deptAcr ? $deptModels[$deptAcr]->id : null,
                    'department'           => $deptAcr ? $deptModels[$deptAcr]->name : null,
                    'platform_fee_paid'    => true,
                    'must_change_password' => false,
                ]
            );
        }

        // Give the sample lecturer a couple of SLT courses (so attendance / exam
        // menu / scoring have data to work with).
        $lecturer = User::where('email', 'lecturer@mahhfaz.edu.ng')->first();
        if ($lecturer) {
            $sltSubjectIds = Subject::where('department_id', $deptModels['SLT']->id)->pluck('id');
            $lecturer->subjects()->syncWithoutDetaching($sltSubjectIds);
        }

        // --- Sample STUDENT + linked Student record (fully registered) ---
        $studentEmail = 'student@mahhfaz.edu.ng';
        User::updateOrCreate(
            ['email' => $studentEmail],
            [
                'name'                 => 'Sample Student',
                'password'             => Hash::make('password'),
                'role'                 => 'student',
                'college_id'           => $mahhfaz->id,
                'platform_fee_paid'    => true,
                'must_change_password' => false,
            ]
        );

        $ndSlt = $progModels['ND-SLT'];
        \App\Models\Student::updateOrCreate(
            ['email' => $studentEmail],
            [
                'full_name'           => 'Sample Student',
                'admission_number'    => $mahhfaz->acronym.'/ADM/'.date('Y').'/0001',
                'registration_number' => app(\App\Services\StudentIdGenerator::class)->generate($ndSlt),
                'college_id'          => $mahhfaz->id,
                'department_id'       => $ndSlt->department_id,
                'program_id'          => $ndSlt->id,
                'level'               => '100',
                'class_arm'           => $ndSlt->name,
                'parent_phone'        => '+234 800 000 0000',
                'fees_balance'        => 0,
                'registration_status' => 'registered',
            ]
        );
    }
}
