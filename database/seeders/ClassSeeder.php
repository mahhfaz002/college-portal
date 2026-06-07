<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ClassSeeder extends Seeder
{
    /**
     * Build the managed class registry from any existing student class
     * labels plus a sensible default ladder, then wire the demo teacher
     * to a class and a couple of subjects through the new pivot tables.
     */
    public function run(): void
    {
        $defaults = ['JSS1A', 'JSS2A', 'JSS3A', 'SSS1A', 'SSS2A', 'SSS3A'];

        $fromStudents = Student::query()
            ->whereNotNull('class_arm')
            ->distinct()
            ->pluck('class_arm')
            ->all();

        $names = collect($defaults)->merge($fromStudents)->filter()->unique();

        foreach ($names as $name) {
            SchoolClass::firstOrCreate(
                ['name' => $name],
                [
                    'level'   => Str::startsWith($name, 'SSS') ? 'SSS' : (Str::startsWith($name, 'JSS') ? 'JSS' : null),
                    'section' => Str::substr($name, -1),
                    'active'  => true,
                ]
            );
        }

        // Wire the demo teacher to JSS1A + Mathematics/English via pivots.
        $teacher = User::where('email', 'teacher@mahhfaz.edu')->first();
        if ($teacher) {
            $teacher->update(['class_assigned' => 'JSS1A', 'subject_assigned' => 'Mathematics']);

            $jss1a = SchoolClass::where('name', 'JSS1A')->first();
            if ($jss1a) {
                $teacher->classes()->syncWithoutDetaching([$jss1a->id]);
            }

            $subjectIds = Subject::whereIn('name', ['Mathematics', 'English Language'])->pluck('id')->all();
            if ($subjectIds) {
                $teacher->subjects()->syncWithoutDetaching($subjectIds);
            }
        }
    }
}
