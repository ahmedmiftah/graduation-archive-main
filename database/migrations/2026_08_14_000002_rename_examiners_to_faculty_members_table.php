<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('examiners', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn(['title', 'department_id']);
            $table->string('phone_number')->after('full_name');
            $table->string('email')->after('phone_number');
            $table->foreignId('degree_id')->nullable()->after('email')->constrained('academic_degrees')->restrictOnDelete();
        });

        Schema::rename('examiners', 'faculty_members');
    }

    public function down(): void
    {
        Schema::rename('faculty_members', 'examiners');

        Schema::table('examiners', function (Blueprint $table) {
            $table->dropForeign(['degree_id']);
            $table->dropColumn(['phone_number', 'email', 'degree_id']);
            $table->string('title')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
        });
    }
};
