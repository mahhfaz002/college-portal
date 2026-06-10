<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — basic feature modules for the remaining roles:
 *  - Librarian:        college_id scoping on the existing books / borrow_records
 *  - Student Affairs:  student_affairs_cases
 *  - Office Secretary: correspondences
 */
return new class extends Migration
{
    public function up(): void
    {
        // Scope the existing library tables per college + add a category.
        Schema::table('books', function (Blueprint $table) {
            if (!Schema::hasColumn('books', 'college_id')) {
                $table->unsignedBigInteger('college_id')->nullable()->index();
            }
            if (!Schema::hasColumn('books', 'category')) {
                $table->string('category')->nullable();
            }
        });
        Schema::table('borrow_records', function (Blueprint $table) {
            if (!Schema::hasColumn('borrow_records', 'college_id')) {
                $table->unsignedBigInteger('college_id')->nullable()->index();
            }
        });

        Schema::create('student_affairs_cases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_id')->nullable()->index();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('student_name')->nullable();
            $table->string('category')->default('welfare'); // disciplinary | welfare | complaint
            $table->text('description');
            $table->string('status')->default('open'); // open | resolved
            $table->unsignedBigInteger('logged_by')->nullable();
            $table->timestamps();
        });

        Schema::create('correspondences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_id')->nullable()->index();
            $table->string('ref_no')->nullable();
            $table->string('direction')->default('incoming'); // incoming | outgoing
            $table->string('subject');
            $table->string('party')->nullable();        // from (incoming) / to (outgoing)
            $table->date('dated')->nullable();
            $table->string('status')->default('received'); // received | filed | forwarded
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correspondences');
        Schema::dropIfExists('student_affairs_cases');
    }
};
