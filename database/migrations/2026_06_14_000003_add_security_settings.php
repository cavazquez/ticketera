<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('allow_public_registration')->default(false)->after('auto_assign_tickets');
            $table->boolean('turnstile_enabled')->default(false)->after('allow_public_registration');
            $table->string('turnstile_site_key')->nullable()->after('turnstile_enabled');
            $table->text('turnstile_secret_key')->nullable()->after('turnstile_site_key');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'allow_public_registration',
                'turnstile_enabled',
                'turnstile_site_key',
                'turnstile_secret_key',
            ]);
        });
    }
};
