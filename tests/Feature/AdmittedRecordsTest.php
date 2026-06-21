<?php

namespace Tests\Feature;

use App\Models\AdmittedRecord;
use App\Models\Department;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class AdmittedRecordsTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    private Program $program;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        // host 'localhost' so the public self-registration resolves this tenant.
        $this->bootCollege(['domain' => 'localhost']);

        $dept = Department::create(['name' => 'Health Sciences', 'acronym' => 'HS', 'section' => 'UG']);
        $this->program = Program::create(['name' => 'Nursing', 'acronym' => 'NUR', 'department_id' => $dept->id]);
    }

    public function test_superadmin_imports_admitted_records_from_csv(): void
    {
        $csv = "Full Name,Registration Number,Department,Level\n"
             ."John Doe,REG/2026/001,Nursing,100\n"
             ."Jane Smith,REG/2026/002,Nursing,200\n";
        $file = UploadedFile::fake()->createWithContent('admitted.csv', $csv);

        $this->actingAs($this->userWithRole('superadmin'))
            ->post('/platform/admitted-records', ['college_id' => $this->college->id, 'csv' => $file])
            ->assertRedirect();

        $this->assertDatabaseHas('admitted_records', [
            'college_id' => $this->college->id, 'registration_number' => 'REG/2026/001', 'full_name' => 'John Doe', 'level' => '100',
        ]);
        $this->assertSame(2, AdmittedRecord::withoutGlobalScopes()->count());
    }

    public function test_lookup_finds_record_or_tells_student_to_see_registrar(): void
    {
        AdmittedRecord::create([
            'college_id' => $this->college->id, 'full_name' => 'John Doe',
            'registration_number' => 'REG/2026/001', 'department' => 'Nursing', 'level' => '100',
        ]);

        // Known reg number → prefilled form renders.
        $this->post('/student/register/lookup', ['registration_number' => 'REG/2026/001'])
            ->assertOk()->assertSee('John Doe');

        // Unknown reg number → sent back with a "contact the Registrar" error.
        $this->post('/student/register/lookup', ['registration_number' => 'NOPE/999'])
            ->assertSessionHasErrors('registration_number');
    }

    public function test_store_requires_a_valid_admitted_record(): void
    {
        // No matching record → rejected, no account created.
        $this->post('/student/register', [
            'registration_number' => 'GHOST/1', 'program_id' => $this->program->id,
            'phone' => '080', 'email' => 'ghost@example.test', 'address' => '1 Rd',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
            'passport' => UploadedFile::fake()->create('p.jpg', 100, 'image/jpeg'),
        ])->assertRedirect(route('student.register'));

        $this->assertDatabaseMissing('students', ['email' => 'ghost@example.test']);
    }

    public function test_valid_registration_creates_account_and_claims_record(): void
    {
        $record = AdmittedRecord::create([
            'college_id' => $this->college->id, 'full_name' => 'John Doe',
            'registration_number' => 'REG/2026/001', 'department' => 'Nursing', 'level' => '100',
        ]);

        $this->post('/student/register', [
            'registration_number' => 'REG/2026/001', 'program_id' => $this->program->id,
            'phone' => '08011112222', 'email' => 'john@example.test', 'address' => '1 College Rd',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
            'passport' => UploadedFile::fake()->create('passport.jpg', 100, 'image/jpeg'),
        ])->assertRedirect(); // → payment checkout

        $this->assertDatabaseHas('users', ['email' => 'john@example.test', 'role' => 'student']);
        $this->assertDatabaseHas('students', [
            'registration_number' => 'REG/2026/001', 'full_name' => 'John Doe',
            'program_id' => $this->program->id, 'level' => '100',
        ]);
        $this->assertNotNull($record->fresh()->claimed_at);
    }

    public function test_per_college_list_shows_records_with_status(): void
    {
        // Unclaimed → pending.
        AdmittedRecord::create([
            'college_id' => $this->college->id, 'full_name' => 'Pending Pat',
            'registration_number' => 'REG/2026/010', 'department' => 'Nursing', 'level' => '100',
        ]);

        // Claimed by a fee-paid user → registered.
        $paid = User::factory()->role('student')->create(['platform_fee_paid' => true]);
        AdmittedRecord::create([
            'college_id' => $this->college->id, 'full_name' => 'Registered Reg',
            'registration_number' => 'REG/2026/011', 'department' => 'Nursing', 'level' => '200',
            'claimed_at' => now(), 'claimed_by' => $paid->id,
        ]);

        $this->actingAs($this->userWithRole('superadmin'))
            ->get(route('platform.colleges.students', $this->college))
            ->assertOk()
            ->assertSee('Pending Pat')->assertSee('Pending')
            ->assertSee('Registered Reg')->assertSee('Registered');
    }

    public function test_registered_students_search_filters_results(): void
    {
        AdmittedRecord::create([
            'college_id' => $this->college->id, 'full_name' => 'Aisha Bello',
            'registration_number' => 'REG/2026/020', 'department' => 'Nursing', 'level' => '100',
        ]);
        AdmittedRecord::create([
            'college_id' => $this->college->id, 'full_name' => 'Yusuf Musa',
            'registration_number' => 'REG/2026/021', 'department' => 'Pharmacy', 'level' => '100',
        ]);

        $this->actingAs($this->userWithRole('superadmin'))
            ->get(route('platform.colleges.students', $this->college).'?q=Aisha')
            ->assertOk()->assertSee('Aisha Bello')->assertDontSee('Yusuf Musa');
    }

    public function test_superadmin_edits_an_uploaded_record(): void
    {
        $record = AdmittedRecord::create([
            'college_id' => $this->college->id, 'full_name' => 'Jon Doe',
            'registration_number' => 'REG/2026/030', 'department' => 'Nursing', 'level' => '100',
        ]);

        $this->actingAs($this->userWithRole('superadmin'))
            ->put(route('platform.colleges.students.update', [$this->college, $record]), [
                'full_name' => 'John Doe', 'registration_number' => 'REG/2026/031',
                'department' => 'Nursing', 'level' => '200',
            ])->assertRedirect();

        $this->assertDatabaseHas('admitted_records', [
            'id' => $record->id, 'full_name' => 'John Doe',
            'registration_number' => 'REG/2026/031', 'level' => '200',
        ]);
    }
}
