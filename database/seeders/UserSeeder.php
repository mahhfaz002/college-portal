<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['Proprietor Office', 'proprietor@mahhfaz.edu', 'proprietor'],
            ['School Principal',  'principal@mahhfaz.edu',  'principal'],
            ['System Admin',      'admin@mahhfaz.edu',      'admin'],
            ['ICT Administrator', 'ict@mahhfaz.edu',        'ict'],
            ['School Bursar',     'bursar@mahhfaz.edu',     'accountant'],
            ['Exam Officer',      'exams@mahhfaz.edu',      'exam_officer'],
            ['Mr. Jalingo',       'teacher@mahhfaz.edu',    'teacher'],
        ];

        foreach ($users as [$name, $email, $role]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'role' => $role,
                    'must_change_password' => false,
                ]
            );
        }

        // Give the demo teacher a class so their dashboard has data.
        User::where('email', 'teacher@excellence.edu')->update(['class_assigned' => 'JSS1A']);
    }
}
