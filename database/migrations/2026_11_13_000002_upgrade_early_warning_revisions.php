<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi Revisi "Cegah Sebelum Terlambat":
 * 1. Ekspansi 4 -> 10 variabel inti pada historical_records & penanda data sintetis pada historical_datasets.
 * 2. Kolom komponen nilai berjalan (UTS, Tugas, UAS), sumber data, tingkat kepercayaan, dan proyeksi (N & U_min) pada academic_records.
 * 3. Metadata keterbukaan komponen, tingkat kepercayaan, selisih proyeksi vs KHS, dan penanda saran LLM pada risk_results.
 * 4. Tabel baru risk_snapshots untuk jejak cek risiko berkala selama semester berjalan.
 * 5. Kolom pemantauan DPA (status tindak lanjut, keaktifan 3 tingkat, status tugas akhir, tanggal bimbingan terakhir) pada student_settings.
 * 6. Tabel baru guidance_notes (catatan bimbingan DPA), dpa_notifications (pemberitahuan kenaikan risiko ke Tinggi), dan academic_calendars.
 * 7. Penyesuaian access_logs (student_ref nullable) untuk mendukung 1 log per kunjungan daftar bimbingan DPA.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. D5: Penanda dataset sintetis & ekspansi 10 variabel inti pada data historis
        Schema::table('historical_datasets', function (Blueprint $table) {
            $table->boolean('is_synthetic')->default(false)->after('label_counts');
        });

        Schema::table('historical_records', function (Blueprint $table) {
            // 4 fitur lama sudah ada: attendance, ips, failed_ratio, ips_delta
            // Tambahan 6 fitur inti baru agar genap 10 variabel inti:
            $table->decimal('min_attendance', 5, 2)->default(85.00)->after('attendance');
            $table->unsignedTinyInteger('low_attendance_courses')->default(0)->after('min_attendance');
            $table->decimal('attendance_delta', 5, 2)->default(0.00)->after('low_attendance_courses');
            $table->decimal('ipk', 4, 2)->default(3.00)->after('ips');
            $table->unsignedTinyInteger('repeat_courses')->default(0)->after('failed_ratio');
            $table->smallInteger('credits_delta')->default(0)->after('repeat_courses');
        });

        // 2. D2: Kolom proyeksi per mata kuliah, komponen nilai (UTS, Tugas, UAS), dan sumber data
        Schema::table('academic_records', function (Blueprint $table) {
            // Dibuat nullable agar mata kuliah semester berjalan (sebelum KHS resmi terbit) bisa disimpan
            $table->decimal('grade_point', 3, 2)->nullable()->change();
            $table->string('grade_input', 12)->nullable()->change();

            // Komponen nilai opsional semester berjalan (skala 0-100)
            $table->decimal('uts_score', 5, 2)->nullable()->after('attendance');
            $table->decimal('assignment_score', 5, 2)->nullable()->after('uts_score');
            $table->decimal('uas_score', 5, 2)->nullable()->after('assignment_score');

            // Sumber rekap tugas & waktu pencatatan: 'mandiri' | 'olu' | 'gdrive'
            $table->string('assignment_source', 20)->nullable()->after('uas_score');
            $table->date('assignment_recorded_at')->nullable()->after('assignment_source');

            // Hasil proyeksi per mata kuliah (N, U_min untuk C=56, U_min untuk B=68, dan level risiko MK)
            $table->decimal('projected_score', 5, 2)->nullable()->after('assignment_recorded_at');
            $table->decimal('min_uas_for_c', 5, 2)->nullable()->after('projected_score');
            $table->decimal('min_uas_for_b', 5, 2)->nullable()->after('min_uas_for_c');
            $table->string('course_risk_level', 10)->nullable()->after('min_uas_for_b'); // rendah | sedang | tinggi

            // Tingkat kepercayaan data: 1 = Sistem akademik/KHS, 2 = Terkonfirmasi dosen/DPA, 3 = Dilaporkan sendiri
            $table->unsignedTinyInteger('data_trust_level')->default(1)->after('course_risk_level');
            $table->boolean('is_official_khs')->default(true)->after('data_trust_level');
        });

        // 3. D3: Metadata keterbukaan hasil risiko & penanda penjelasan AI LLM
        Schema::table('risk_results', function (Blueprint $table) {
            $table->unsignedTinyInteger('components_count')->default(4)->after('score');
            $table->unsignedTinyInteger('data_trust_level')->default(1)->after('components_count');
            $table->boolean('is_projection')->default(false)->after('data_trust_level');
            $table->boolean('projection_gap_flagged')->default(false)->after('is_projection');
            $table->boolean('llm_generated')->default(false)->after('suggestions');
        });

        // 4. D3: Tabel jejak cek risiko berkala selama semester (risk_snapshots)
        Schema::create('risk_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('student_ref', 64)->index();
            $table->foreignId('risk_result_id')->nullable()->constrained('risk_results')->cascadeOnDelete();
            $table->unsignedTinyInteger('semester');
            // Fase cek: awal_semester | setelah_uts | tengah_semester | sebelum_uas | khs_terbit
            $table->string('phase', 24)->default('awal_semester');
            $table->string('level', 10); // rendah | sedang | tinggi
            $table->decimal('score', 4, 2);
            $table->unsignedTinyInteger('components_count')->default(1);
            $table->unsignedTinyInteger('data_trust_level')->default(1);
            $table->json('features');
            $table->json('course_projections')->nullable();
            $table->timestamp('checked_at')->useCurrent();
            $table->timestamps();

            $table->index(['student_ref', 'semester', 'checked_at']);
        });

        // 5. D4: Input penilaian DPA & status tindak lanjut bimbingan pada student_settings
        Schema::table('student_settings', function (Blueprint $table) {
            // Status tindak lanjut: belum_dihubungi | dijadwalkan | sudah_bimbingan | selesai
            $table->string('follow_up_status', 24)->default('belum_dihubungi')->index()->after('remind_email');
            // Keaktifan penilaian DPA: aktif | cukup | kurang
            $table->string('dpa_activity_level', 16)->nullable()->after('follow_up_status');
            // Status tugas akhir (khusus semester >= 7): belum_mulai | proposal | pengerjaan | sidang_terjadwal | selesai
            $table->string('thesis_status', 24)->nullable()->after('dpa_activity_level');
            $table->date('last_guidance_at')->nullable()->after('thesis_status');
            $table->boolean('dpa_confirmed_tasks')->default(false)->after('last_guidance_at');
            $table->foreignId('dpa_updated_by')->nullable()->after('dpa_confirmed_tasks')
                ->constrained('users')->nullOnDelete();
        });

        // 6. D4: Tabel catatan bimbingan DPA per pertemuan (guidance_notes)
        Schema::create('guidance_notes', function (Blueprint $table) {
            $table->id();
            $table->string('student_ref', 64)->index();
            $table->foreignId('dpa_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('semester')->nullable();
            $table->date('guidance_date');
            $table->string('topic', 160);
            $table->text('follow_up_action');
            $table->string('follow_up_status', 24)->default('sudah_bimbingan');
            $table->string('activity_assessment', 16)->nullable(); // aktif | cukup | kurang
            $table->string('thesis_status', 24)->nullable();
            $table->boolean('confirmed_course_scores')->default(false);
            $table->timestamps();

            $table->index(['student_ref', 'guidance_date']);
            $table->index(['dpa_id', 'guidance_date']);
        });

        // 7. D4: Tabel pemberitahuan di dalam aplikasi untuk DPA saat mahasiswa izin naik ke Tinggi
        Schema::create('dpa_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dpa_id')->constrained('users')->cascadeOnDelete();
            $table->string('student_ref', 64)->index();
            $table->foreignId('risk_result_id')->nullable()->constrained('risk_results')->nullOnDelete();
            $table->unsignedTinyInteger('semester');
            $table->string('previous_level', 10)->nullable();
            $table->string('current_level', 10); // tinggi
            $table->text('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['dpa_id', 'read_at']);
        });

        // 8. Kalender akademik per semester untuk jadwal pengingat UTS, UAS, dan rilis KHS
        Schema::create('academic_calendars', function (Blueprint $table) {
            $table->id();
            $table->string('academic_year', 16); // contoh: 2026/2027
            $table->string('term', 10);          // gasal | genap
            $table->date('uts_start_date')->nullable();
            $table->date('uts_end_date')->nullable();
            $table->date('uas_start_date')->nullable();
            $table->date('uas_end_date')->nullable();
            $table->date('khs_release_date')->nullable();
            $table->boolean('is_current')->default(false)->index();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['academic_year', 'term']);
        });

        // 9. D6: Ubah student_ref pada access_logs menjadi nullable agar kunjungan daftar bimbingan DPA tercatat 1 baris per kunjungan
        Schema::table('access_logs', function (Blueprint $table) {
            $table->string('student_ref', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            $table->string('student_ref', 64)->nullable(false)->change();
        });

        Schema::dropIfExists('academic_calendars');
        Schema::dropIfExists('dpa_notifications');
        Schema::dropIfExists('guidance_notes');
        Schema::dropIfExists('risk_snapshots');

        Schema::table('student_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dpa_updated_by');
            $table->dropColumn([
                'follow_up_status',
                'dpa_activity_level',
                'thesis_status',
                'last_guidance_at',
                'dpa_confirmed_tasks',
            ]);
        });

        Schema::table('risk_results', function (Blueprint $table) {
            $table->dropColumn([
                'components_count',
                'data_trust_level',
                'is_projection',
                'projection_gap_flagged',
                'llm_generated',
            ]);
        });

        Schema::table('academic_records', function (Blueprint $table) {
            $table->dropColumn([
                'uts_score',
                'assignment_score',
                'uas_score',
                'assignment_source',
                'assignment_recorded_at',
                'projected_score',
                'min_uas_for_c',
                'min_uas_for_b',
                'course_risk_level',
                'data_trust_level',
                'is_official_khs',
            ]);
            $table->decimal('grade_point', 3, 2)->nullable(false)->change();
            $table->string('grade_input', 12)->nullable(false)->change();
        });

        Schema::table('historical_records', function (Blueprint $table) {
            $table->dropColumn([
                'min_attendance',
                'low_attendance_courses',
                'attendance_delta',
                'ipk',
                'repeat_courses',
                'credits_delta',
            ]);
        });

        Schema::table('historical_datasets', function (Blueprint $table) {
            $table->dropColumn('is_synthetic');
        });
    }
};