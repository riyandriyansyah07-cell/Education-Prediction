<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code', 24)->unique();
            // jenis: akun_mahasiswa | akun_dpa | reset_sandi
            $table->string('request_type', 24)->default('akun_mahasiswa');
            $table->string('name', 100);
            $table->string('username', 32)->index(); // NIM atau ID pegawai
            $table->string('email', 120)->nullable();
            $table->text('notes')->nullable();
            // status: menunggu | disetujui | ditolak
            $table->string('status', 16)->default('menunggu')->index();
            $table->text('admin_note')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_tickets');
    }
};