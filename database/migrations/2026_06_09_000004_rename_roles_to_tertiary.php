<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rewrites legacy primary/secondary role names to the tertiary role model.
 * Safe to run on existing data; migrate:fresh installs already seed new names.
 */
return new class extends Migration
{
    private array $map = [
        'principal'  => 'registrar',
        'admin'      => 'registrar',  // admin duties absorbed by the Registrar
        'accountant' => 'bursar',
        'teacher'    => 'lecturer',
    ];

    public function up(): void
    {
        foreach ($this->map as $old => $new) {
            DB::table('users')->where('role', $old)->update(['role' => $new]);
        }
    }

    public function down(): void
    {
        // Best-effort reverse (admin/principal collision is not restorable).
        DB::table('users')->where('role', 'bursar')->update(['role' => 'accountant']);
        DB::table('users')->where('role', 'lecturer')->update(['role' => 'teacher']);
        DB::table('users')->where('role', 'registrar')->update(['role' => 'principal']);
    }
};
