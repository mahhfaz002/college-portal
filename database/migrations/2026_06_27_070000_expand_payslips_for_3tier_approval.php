<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->string('provost_status')->nullable()->default('pending');
            $table->text('provost_comment')->nullable();
            $table->timestamp('provost_reviewed_at')->nullable();
            $table->string('proprietor_status')->nullable()->default('pending');
            $table->text('proprietor_comment')->nullable();
            $table->timestamp('proprietor_approved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $cols = ['provost_status', 'provost_comment', 'provost_reviewed_at',
                     'proprietor_status', 'proprietor_comment', 'proprietor_approved_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('payslips', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
