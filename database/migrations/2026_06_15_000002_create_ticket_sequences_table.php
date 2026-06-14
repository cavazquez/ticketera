<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('last_number')->default(0);
        });

        $lastNumber = (int) (DB::table('tickets')->max('id') ?? 0);

        DB::table('ticket_sequences')->insert(['last_number' => $lastNumber]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_sequences');
    }
};
