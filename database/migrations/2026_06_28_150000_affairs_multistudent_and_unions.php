<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A case can now involve multiple students.
        Schema::table('student_affairs_cases', function (Blueprint $table) {
            if (! Schema::hasColumn('student_affairs_cases', 'student_ids')) {
                $table->json('student_ids')->nullable();
            }
        });

        // Student unions / associations / organisations.
        if (! Schema::hasTable('student_unions')) {
            Schema::create('student_unions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('college_id')->nullable()->index();
                $table->string('name');
                $table->string('acronym')->nullable();
                $table->year('year_established')->nullable();
                $table->text('constituents')->nullable();   // who the union represents
                $table->unsignedInteger('members_count')->default(0);
                $table->string('status')->default('active'); // active | suspended
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('student_union_leaders')) {
            Schema::create('student_union_leaders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_union_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('department')->nullable();
                $table->string('course_of_study')->nullable();
                $table->string('level')->nullable();
                $table->string('position');                 // President, Secretary, …
                $table->date('tenure_start')->nullable();
                $table->date('tenure_end')->nullable();      // one year from start
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_union_leaders');
        Schema::dropIfExists('student_unions');
        Schema::table('student_affairs_cases', function (Blueprint $table) {
            if (Schema::hasColumn('student_affairs_cases', 'student_ids')) {
                $table->dropColumn('student_ids');
            }
        });
    }
};
