<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('imported_student_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('imported_student_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('full_name')->nullable();
            $table->string('national_id')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('status'); // success | duplicate_in_file | already_exists | invalid_national_id | invalid_registration_number | invalid_data
            $table->string('error_message')->nullable();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imported_student_rows');
    }
};
