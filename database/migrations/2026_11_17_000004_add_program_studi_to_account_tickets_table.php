<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_tickets', function (Blueprint $table) {
            $table->string('program_studi', 80)->nullable()->after('username');
        });
    }

    public function down(): void
    {
        Schema::table('account_tickets', function (Blueprint $table) {
            $table->dropColumn('program_studi');
        });
    }
};