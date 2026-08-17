<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->foreignId('faculty_member_id')->nullable()->after('project_id')->constrained('faculty_members')->cascadeOnDelete();
        });

        DB::statement('UPDATE evaluations SET faculty_member_id = examiner_id');

        Schema::table('evaluations', function (Blueprint $table) {
            $table->foreignId('faculty_member_id')->nullable(false)->change();
        });

        if (Schema::hasColumn('evaluations', 'examiner_id')) {
            $hasForeignKey = $this->hasForeignKey('evaluations', 'examiner_id');

            Schema::table('evaluations', function (Blueprint $table) use ($hasForeignKey) {
                if ($hasForeignKey) {
                    $table->dropForeign(['examiner_id']);
                }
                $table->dropColumn('examiner_id');
            });
        }
    }

    private function hasForeignKey(string $table, string $column): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return true;
        }

        return collect(DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$table, $column]
        ))->isNotEmpty();
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropForeign(['faculty_member_id']);
            $table->dropColumn('faculty_member_id');
            $table->foreignId('examiner_id')->after('project_id')->constrained('examiners')->cascadeOnDelete();
        });
    }
};
