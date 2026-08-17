<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('max_students_per_project')->default(5);
            $table->unsignedInteger('examiners_per_project')->default(2);
            $table->enum('academic_year_format', ['2_digit', '4_digit'])->default('4_digit');
            $table->boolean('archive_enabled')->default(true);
            $table->json('archivable_proposal_statuses')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
