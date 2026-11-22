<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrichment_records', function (Blueprint $table) {
            $table->id();
            $table->string('student_ref', 64)->index();
            $table->string('course_code', 48);
            $table->string('course_name', 120);
            $table->unsignedTinyInteger('credits');
            $table->string('competency', 80);
            $table->string('lecturer', 120);
            $table->string('activity_type', 160);
            $table->unsignedTinyInteger('activity_semester');
            $table->unsignedTinyInteger('conversion_semester')->nullable();
            $table->string('status', 24)->default('dicicil');
            $table->string('grade', 4)->nullable();
            $table->string('evidence_notes', 500)->nullable();
            $table->timestamps();

            $table->unique(['student_ref', 'course_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrichment_records');
    }
};