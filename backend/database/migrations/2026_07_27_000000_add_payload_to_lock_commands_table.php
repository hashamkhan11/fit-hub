<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lock_commands', function (Blueprint $table) {
            $table->json('payload')->nullable()->after('action');
            $table->string('progress_message')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('lock_commands', function (Blueprint $table) {
            $table->dropColumn(['payload', 'progress_message']);
        });
    }
};
