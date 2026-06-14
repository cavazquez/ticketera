<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['status', 'department_id', 'created_at'], 'tickets_queue_index');
            $table->index(['assigned_to', 'status'], 'tickets_assignee_status_index');
            $table->index(['user_id', 'created_at'], 'tickets_user_created_index');
            $table->index('due_at', 'tickets_due_at_index');
        });

        Schema::table('ticket_replies', function (Blueprint $table) {
            $table->index(['ticket_id', 'created_at'], 'ticket_replies_ticket_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_queue_index');
            $table->dropIndex('tickets_assignee_status_index');
            $table->dropIndex('tickets_user_created_index');
            $table->dropIndex('tickets_due_at_index');
        });

        Schema::table('ticket_replies', function (Blueprint $table) {
            $table->dropIndex('ticket_replies_ticket_created_index');
        });
    }
};
