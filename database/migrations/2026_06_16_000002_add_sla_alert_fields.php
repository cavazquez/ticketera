<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('sla_warning_hours')->default(2)->after('sla_urgente_hours');
            $table->boolean('notify_sla_warnings')->default(true)->after('sla_warning_hours');
            $table->boolean('notify_sla_breaches')->default(true)->after('notify_sla_warnings');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('sla_warning_sent_at')->nullable()->after('due_at');
            $table->timestamp('sla_breach_sent_at')->nullable()->after('sla_warning_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['sla_warning_hours', 'notify_sla_warnings', 'notify_sla_breaches']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['sla_warning_sent_at', 'sla_breach_sent_at']);
        });
    }
};
