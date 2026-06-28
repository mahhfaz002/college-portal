<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Store e-signatures directly in the database (as a base64 data URI) instead of
 * on the object-storage disk. The disk write was the cause of the Registrar /
 * Provost "could not save" failures on production (S3 disk misconfiguration),
 * and the filesystem is also ephemeral across deploys. A small signature PNG in
 * its own table is durable and has no external dependency. Kept off the users
 * table so it is never loaded on every authenticated request.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_signatures')) {
            Schema::create('user_signatures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->longText('data'); // full data URI: data:image/png;base64,....
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_signatures');
    }
};
