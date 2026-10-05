<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->unsignedSmallInteger('fingerprint_id')->nullable()->after('photo_path');
            $table->unique(['gym_id', 'fingerprint_id']);
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['gym_id', 'fingerprint_id']);
            $table->dropColumn('fingerprint_id');
        });
    }
};
