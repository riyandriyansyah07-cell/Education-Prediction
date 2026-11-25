<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'program_studi')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('program_studi', 80)->nullable()->after('username')->index();
            });
        }

        // Isi otomatis program_studi mahasiswa dari riwayat account_tickets bila tersedia
        if (Schema::hasTable('account_tickets') && Schema::hasColumn('account_tickets', 'program_studi')) {
            $tickets = DB::table('account_tickets')
                ->whereNotNull('program_studi')
                ->where('program_studi', '!=', '')
                ->orderByDesc('id')
                ->get(['username', 'program_studi']);

            $seen = [];
            foreach ($tickets as $t) {
                $uname = (string) $t->username;
                if (isset($seen[$uname])) {
                    continue;
                }
                $seen[$uname] = true;

                DB::table('users')
                    ->where('username', $uname)
                    ->where('role', 'mahasiswa')
                    ->whereNull('program_studi')
                    ->update(['program_studi' => $t->program_studi]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'program_studi')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['program_studi']);
                $table->dropColumn('program_studi');
            });
        }
    }
};
