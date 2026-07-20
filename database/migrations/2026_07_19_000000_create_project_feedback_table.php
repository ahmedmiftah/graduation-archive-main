<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('department_id')->constrained('departments');
            $table->string('visitor_name')->nullable();
            $table->string('visitor_email')->nullable();
            $table->string('feedback_type', 32);
            $table->string('title');
            $table->text('message');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->boolean('is_read')->default(false);
            $table->string('status', 32)->default('new');
            $table->timestamp('responded_at')->nullable();
            $table->string('project_title');
            $table->string('department_name');
            $table->text('student_names')->nullable();
            $table->timestamps();

            $table->index(['feedback_type', 'status', 'rating', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_feedback');
    }
};
