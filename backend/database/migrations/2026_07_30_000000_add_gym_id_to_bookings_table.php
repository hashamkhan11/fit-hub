<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('gym_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        DB::statement(
            'UPDATE bookings '.
            'JOIN gym_classes ON gym_classes.id = bookings.gym_class_id '.
            'SET bookings.gym_id = gym_classes.gym_id'
        );

        // Using raw SQL here since the normal column-change method needs a package we don't have.
        DB::statement('ALTER TABLE bookings MODIFY gym_id BIGINT UNSIGNED NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gym_id');
        });
    }
};
