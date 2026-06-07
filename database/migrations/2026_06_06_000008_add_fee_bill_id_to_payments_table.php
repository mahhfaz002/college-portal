<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'fee_bill_id')) {
                $table->foreignId('fee_bill_id')->nullable()->after('student_id')
                    ->constrained('fee_bills')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'fee_bill_id')) {
                $table->dropConstrainedForeignId('fee_bill_id');
            }
        });
    }
};
