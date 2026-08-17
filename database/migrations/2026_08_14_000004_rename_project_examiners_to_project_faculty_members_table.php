<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add the new column + composite unique first so the old
        // (project_id, examiner_id) unique index is never the last index
        // covering project_id — MySQL refuses to drop an index that a
        // foreign key still relies on.
        Schema::table('project_examiners', function (Blueprint $table) {
            $table->foreignId('faculty_member_id')->nullable()->after('project_id')->constrained('faculty_members')->cascadeOnDelete();
        });

        DB::statement('UPDATE project_examiners SET faculty_member_id = examiner_id');

        Schema::table('project_examiners', function (Blueprint $table) {
            $table->foreignId('faculty_member_id')->nullable(false)->change();
            $table->unique(['project_id', 'faculty_member_id']);
        });

        Schema::table('project_examiners', function (Blueprint $table) {
            $table->dropForeign(['examiner_id']);
            $table->dropUnique(['project_id', 'examiner_id']);
            $table->dropColumn('examiner_id');
        });

        Schema::rename('project_examiners', 'project_faculty_members');
    }

    public function down(): void
    {
        Schema::rename('project_faculty_members', 'project_examiners');

        Schema::table('project_examiners', function (Blueprint $table) {
            $table->foreignId('examiner_id')->after('project_id')->constrained('examiners')->cascadeOnDelete();
            $table->unique(['project_id', 'examiner_id']);
        });

        Schema::table('project_examiners', function (Blueprint $table) {
            $table->dropForeign(['faculty_member_id']);
            $table->dropUnique(['project_id', 'faculty_member_id']);
            $table->dropColumn('faculty_member_id');
        });
    }
};
