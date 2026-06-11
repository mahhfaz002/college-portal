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
                'registration_no_format' => '{acronym}/{year}/{dept}/{program}/{serial}',
                'is_active' => true,
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

        $deptModels = [];
        foreach ($departments as [$name, $acr]) {
            $deptModels[$acr] = Department::updateOrCreate(
                ['college_id' => $mahhfaz->id, 'name' => $name],
                ['acronym' => $acr]
            );
        }

        $programs = [
            ['SLT',  'ND Science Laboratory Technology', 'ND-SLT', 'ND', 2, 5000, 20000, 75000],
            ['SLT',  'HND Science Laboratory Technology', 'HND-SLT', 'HND', 2, 7500, 25000, 95000],
            ['CHEW', 'Community Health Extension Worker', 'CHEW', 'Certificate', 3, 5000, 15000, 60000],
            ['EHT',  'ND Environmental Health Technology', 'ND-EHT', 'ND', 2, 5000, 20000, 70000],
            ['HIM',  'ND Health Information Management', 'ND-HIM', 'ND', 2, 5000, 20000, 70000],
            ['PHT',  'ND Pharmacy Technician', 'ND-PHT', 'ND', 2, 5000, 20000, 80000],
        ];

        $progModels = [];
        foreach ($programs as [$deptAcr, $name, $acr, $level, $yrs, $app, $acc, $reg]) {
            $progModels[$acr] = Program::updateOrCreate(
                ['college_id' => $mahhfaz->id, 'department_id' => $deptModels[$deptAcr]->id, 'name' => $name],
                ['acronym' => $acr, 'level_system' => $level, 'duration_years' => $yrs,
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

        // --- Minimal bootstrap logins (no demo teaching/finance staff) ---
        $users = [
            ['Proprietor (Owner)',  'proprietor@mahhfaz.edu.ng', 'proprietor'],
            ['College Registrar',   'registrar@mahhfaz.edu.ng',  'registrar'],
            ['MIS Administrator',   'mis@mahhfaz.edu.ng',        'mis'],
            ['Admission Officer',   'admissions@mahhfaz.edu.ng', 'admission_officer'],
        ];

        foreach ($users as [$name, $email, $role]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'role' => $role,
                    'college_id' => $mahhfaz->id,
                    'must_change_password' => false,
                ]
            );
        }
    }
}
