<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-tenant branding & routing: each college gets its own domain and the
 * content used to customise its public landing page. (Data isolation is still
 * enforced by the per-row college_id scope — one shared database.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colleges', function (Blueprint $table) {
            $add = [
                'domain'           => fn () => $table->string('domain')->nullable()->unique(),
                'tagline'          => fn () => $table->string('tagline')->nullable(),
                'motto'            => fn () => $table->string('motto')->nullable(),
                'about'            => fn () => $table->text('about')->nullable(),
                'established_year' => fn () => $table->string('established_year')->nullable(),
                'provost_name'     => fn () => $table->string('provost_name')->nullable(),
                'provost_title'    => fn () => $table->string('provost_title')->nullable(),
                'provost_message'  => fn () => $table->text('provost_message')->nullable(),
            ];
            foreach ($add as $col => $def) {
                if (!Schema::hasColumn('colleges', $col)) {
                    $def();
                }
            }
        });
    }

    public function down(): void
    {
        // Non-destructive.
    }
};
