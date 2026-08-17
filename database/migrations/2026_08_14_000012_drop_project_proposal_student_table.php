<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('project_proposal_student');
    }

    public function down(): void
    {
        Schema::create('project_proposal_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('project_proposals')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }
};
