<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_proposal_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('project_proposals')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('registration_number');
            $table->string('phone_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_proposal_students');
    }
};
