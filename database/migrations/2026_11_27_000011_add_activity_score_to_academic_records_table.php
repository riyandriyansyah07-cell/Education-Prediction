<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_records') || Schema::hasColumn('academic_records', 'activity_score')) {
            return;
        }

        Schema::table('academic_records', function (Blueprint $table) {
            $table->decimal('activity_score', 5, 2)->nullable()->after('attendance');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('academic_records') && Schema::hasColumn('academic_records', 'activity_score')) {
            Schema::table('academic_records', function (Blueprint $table) {
                $table->dropColumn('activity_score');
            });
        }
    }
};
