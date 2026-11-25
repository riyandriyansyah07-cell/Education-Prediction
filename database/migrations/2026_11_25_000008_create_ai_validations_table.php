<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_validations')) {
            Schema::create('ai_validations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('risk_result_id')->constrained('risk_results')->cascadeOnDelete()->unique();
                $table->foreignId('validator_id')->nullable()->constrained('users')->nullOnDelete();
                // verdict: sesuai | perlu_koreksi
                $table->string('verdict', 24)->index();
                $table->string('ai_level', 16);
                $table->decimal('ai_score', 5, 2)->default(0);
                $table->string('rule_level', 16);
                $table->decimal('rule_score', 5, 2)->default(0);
                $table->string('expected_level', 16)->nullable();
                $table->boolean('override_applied')->default(false);
                $table->text('notes')->nullable();
                $table->timestamp('validated_at');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_validations');
    }
};
