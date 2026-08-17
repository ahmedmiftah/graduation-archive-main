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
        Schema::create('project_ideas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_member_id')->constrained('faculty_members')->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->foreignId('specialization_id')->constrained('specializations')->restrictOnDelete();
            $table->unsignedTinyInteger('required_students_count')->default(1);
            $table->text('skills')->nullable();
            $table->text('keywords')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('available'); // available | reserved | completed | closed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_ideas');
    }
};
