<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gyms', function (Blueprint $table) {
            $table->string('stripe_id')->nullable()->after('billing_cycle');
            $table->string('pm_type')->nullable()->after('stripe_id');
            $table->string('pm_last_four', 4)->nullable()->after('pm_type');
            $table->foreignId('subscription_plan_id')->nullable()->after('pm_last_four')
                ->constrained('subscription_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gyms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_plan_id');
            $table->dropColumn(['stripe_id', 'pm_type', 'pm_last_four']);
        });
    }
};
