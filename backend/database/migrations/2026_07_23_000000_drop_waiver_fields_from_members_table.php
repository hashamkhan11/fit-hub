<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['waiver_signed_at', 'waiver_signature_name', 'waiver_ip_address']);
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->timestamp('waiver_signed_at')->nullable()->after('join_date');
            $table->string('waiver_signature_name')->nullable()->after('waiver_signed_at');
            $table->string('waiver_ip_address', 45)->nullable()->after('waiver_signature_name');
        });
    }
};
