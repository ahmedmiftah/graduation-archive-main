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
        Schema::create('proposal_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_idea_id')->constrained('project_ideas')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('project_idea_request_id')->nullable()->constrained('project_idea_requests')->nullOnDelete();
            $table->string('status')->default('reserved'); // reserved | under_review | approved | abandoned | released | finished
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_reservations');
    }
};
