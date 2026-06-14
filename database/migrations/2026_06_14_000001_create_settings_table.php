<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->default('Ticketera');
            $table->string('support_email')->nullable();
            $table->boolean('notify_on_reply')->default(true);
            $table->boolean('notify_on_status_change')->default(true);
            $table->boolean('auto_assign_tickets')->default(false);
            $table->unsignedSmallInteger('sla_baja_hours')->nullable();
            $table->unsignedSmallInteger('sla_normal_hours')->nullable();
            $table->unsignedSmallInteger('sla_alta_hours')->nullable();
            $table->unsignedSmallInteger('sla_urgente_hours')->nullable();
            $table->timestamps();
        });

        DB::table('settings')->insert([
            'app_name' => 'Ticketera',
            'support_email' => 'soporte@ticketera.test',
            'notify_on_reply' => true,
            'notify_on_status_change' => true,
            'auto_assign_tickets' => false,
            'sla_baja_hours' => 72,
            'sla_normal_hours' => 48,
            'sla_alta_hours' => 24,
            'sla_urgente_hours' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
