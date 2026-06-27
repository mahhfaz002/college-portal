<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            if (!Schema::hasColumn('scores', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('scores', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable();
            }
            if (!Schema::hasColumn('scores', 'transmitted_at')) {
                $table->timestamp('transmitted_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            foreach (['submitted_at', 'transmitted_at'] as $col) {
                if (Schema::hasColumn('scores', $col)) {
                    $table->dropColumn($col);
                }
            }
            if (Schema::hasColumn('scores', 'submitted_by')) {
                $table->dropConstrainedForeignId('submitted_by');
            }
        });
    }
};
