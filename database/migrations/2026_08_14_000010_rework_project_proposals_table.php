<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_proposals', function (Blueprint $table) {
            $table->dropColumn(['pdf_file', 'committee_decision', 'committee_notes', 'status']);
        });

        Schema::table('project_proposals', function (Blueprint $table) {
            $table->string('form_file_path')->nullable()->after('semester');
            $table->string('proposal_file_path')->nullable()->after('form_file_path');
            $table->enum('status', ['pending', 'needs_revision', 'rejected', 'approved', 'superseded'])
                ->default('pending')->after('proposal_file_path');
            $table->text('rejection_reason')->nullable()->after('status');
            $table->foreignId('replaces_proposal_id')->nullable()->after('rejection_reason')
                ->constrained('project_proposals')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_proposals', function (Blueprint $table) {
            $table->dropForeign(['replaces_proposal_id']);
            $table->dropColumn(['replaces_proposal_id', 'rejection_reason', 'status', 'proposal_file_path', 'form_file_path']);
        });

        Schema::table('project_proposals', function (Blueprint $table) {
            $table->enum('status', ['new', 'under_review', 'approved', 'rejected', 'archived'])
                ->default('new')->after('semester');
            $table->string('pdf_file')->nullable();
            $table->enum('committee_decision', ['accepted', 'accepted_with_modifications', 'rejected'])->nullable();
            $table->text('committee_notes')->nullable();
        });
    }
};
