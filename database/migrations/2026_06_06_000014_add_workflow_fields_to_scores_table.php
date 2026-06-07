<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            if (!Schema::hasColumn('scores', 'grade')) {
                $table->string('grade')->nullable();
            }
            if (!Schema::hasColumn('scores', 'total')) {
                $table->integer('total')->nullable();
            }
            // Existing scores default to 'published' so current report cards
            // keep showing; the exam workflow walks draft -> submitted -> published.
            if (!Schema::hasColumn('scores', 'status')) {
                $table->string('status')->default('published');
            }
            if (!Schema::hasColumn('scores', 'exam_id')) {
                $table->foreignId('exam_id')->nullable()->constrained('exams')->nullOnDelete();
            }
            if (!Schema::hasColumn('scores', 'published_at')) {
                $table->dateTime('published_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            foreach (['grade', 'total', 'status', 'published_at'] as $col) {
                if (Schema::hasColumn('scores', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('scores', 'exam_id')) {
                $table->dropConstrainedForeignId('exam_id');
            }
        });
    }
};
