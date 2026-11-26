<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('skpi_documents')) {
            return;
        }

        Schema::create('skpi_documents', function (Blueprint $table) {
            $table->id();
            $table->string('student_ref', 64)->index();
            $table->string('type', 16); // it, baps, pkm, toefl
            $table->string('file_path', 255);
            $table->string('original_name', 180);
            $table->string('mime_type', 80);
            $table->unsignedInteger('file_size')->default(0);
            $table->string('verification_status', 20)->default('menunggu'); // menunggu, terverifikasi, ditolak
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_note', 255)->nullable();
            $table->timestamps();

            $table->unique(['student_ref', 'type']);
            $table->index('verification_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skpi_documents');
    }
};
