<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('student_settings', 'skpi_it_status')) {
                $table->string('skpi_it_status', 20)->default('belum')->after('dpa_updated_by');
            }
            if (! Schema::hasColumn('student_settings', 'skpi_it_provider')) {
                $table->string('skpi_it_provider', 80)->nullable()->after('skpi_it_status');
            }
            if (! Schema::hasColumn('student_settings', 'skpi_baps_status')) {
                $table->string('skpi_baps_status', 20)->default('belum')->after('skpi_it_provider');
            }
            if (! Schema::hasColumn('student_settings', 'skpi_pkm_stage')) {
                $table->string('skpi_pkm_stage', 20)->default('belum')->after('skpi_baps_status');
            }
            if (! Schema::hasColumn('student_settings', 'skpi_toefl_status')) {
                $table->string('skpi_toefl_status', 20)->default('belum')->after('skpi_pkm_stage');
            }
            if (! Schema::hasColumn('student_settings', 'skpi_toefl_score')) {
                $table->unsignedSmallInteger('skpi_toefl_score')->nullable()->after('skpi_toefl_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_settings', function (Blueprint $table) {
            $cols = array_values(array_filter([
                'skpi_it_status',
                'skpi_it_provider',
                'skpi_baps_status',
                'skpi_pkm_stage',
                'skpi_toefl_status',
                'skpi_toefl_score',
            ], fn (string $c) => Schema::hasColumn('student_settings', $c)));

            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
