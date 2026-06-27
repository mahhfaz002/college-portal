<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            // Mandatory contributory savings, stored as a percentage (default 10%),
            // adjustable per payslip by the bursar.
            if (!Schema::hasColumn('payslips', 'contributory_savings')) {
                $table->decimal('contributory_savings', 5, 2)->default(10);
            }
            // `tax` is now interpreted as a PERCENTAGE (not a flat amount).
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            if (Schema::hasColumn('payslips', 'contributory_savings')) {
                $table->dropColumn('contributory_savings');
            }
        });
    }
};
