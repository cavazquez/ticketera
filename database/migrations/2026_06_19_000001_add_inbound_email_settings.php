<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('inbound_email_enabled')->default(false)->after('notify_sla_breaches');
            $table->string('inbound_imap_host')->nullable()->after('inbound_email_enabled');
            $table->unsignedSmallInteger('inbound_imap_port')->default(993)->after('inbound_imap_host');
            $table->string('inbound_imap_encryption', 8)->default('ssl')->after('inbound_imap_port');
            $table->string('inbound_imap_username')->nullable()->after('inbound_imap_encryption');
            $table->text('inbound_imap_password')->nullable()->after('inbound_imap_username');
            $table->string('inbound_imap_folder')->default('INBOX')->after('inbound_imap_password');
            $table->foreignId('inbound_default_department_id')->nullable()->after('inbound_imap_folder')->constrained('departments')->nullOnDelete();
            $table->boolean('inbound_auto_create_users')->default(true)->after('inbound_default_department_id');
        });

        Schema::create('processed_incoming_emails', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique();
            $table->timestamp('processed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('processed_incoming_emails');

        Schema::table('settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inbound_default_department_id');
            $table->dropColumn([
                'inbound_email_enabled',
                'inbound_imap_host',
                'inbound_imap_port',
                'inbound_imap_encryption',
                'inbound_imap_username',
                'inbound_imap_password',
                'inbound_imap_folder',
                'inbound_auto_create_users',
            ]);
        });
    }
};
