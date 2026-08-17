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
        Schema::table('project_proposal_students', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->after('proposal_id')->constrained('students')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_proposal_students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_id');
        });
    }
};
