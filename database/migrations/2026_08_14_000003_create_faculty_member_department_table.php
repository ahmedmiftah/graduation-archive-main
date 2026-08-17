<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_member_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_member_id')->constrained('faculty_members')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['faculty_member_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_member_department');
    }
};
