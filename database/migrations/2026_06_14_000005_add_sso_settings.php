<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('auth_driver')->default('local')->after('turnstile_secret_key');
            $table->boolean('allow_local_login')->default(true)->after('auth_driver');
            $table->boolean('sso_auto_provision')->default(true)->after('allow_local_login');
            $table->string('sso_default_role')->default('cliente')->after('sso_auto_provision');
            $table->string('ldap_host')->nullable()->after('sso_default_role');
            $table->unsignedSmallInteger('ldap_port')->default(389)->after('ldap_host');
            $table->string('ldap_base_dn')->nullable()->after('ldap_port');
            $table->boolean('ldap_use_tls')->default(false)->after('ldap_base_dn');
            $table->string('ldap_username_attribute')->default('mail')->after('ldap_use_tls');
            $table->string('ldap_bind_dn')->nullable()->after('ldap_username_attribute');
            $table->text('ldap_bind_password')->nullable()->after('ldap_bind_dn');
            $table->string('keycloak_base_url')->nullable()->after('ldap_bind_password');
            $table->string('keycloak_realm')->nullable()->after('keycloak_base_url');
            $table->string('keycloak_client_id')->nullable()->after('keycloak_realm');
            $table->text('keycloak_client_secret')->nullable()->after('keycloak_client_id');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'auth_driver',
                'allow_local_login',
                'sso_auto_provision',
                'sso_default_role',
                'ldap_host',
                'ldap_port',
                'ldap_base_dn',
                'ldap_use_tls',
                'ldap_username_attribute',
                'ldap_bind_dn',
                'ldap_bind_password',
                'keycloak_base_url',
                'keycloak_realm',
                'keycloak_client_id',
                'keycloak_client_secret',
            ]);
        });
    }
};
