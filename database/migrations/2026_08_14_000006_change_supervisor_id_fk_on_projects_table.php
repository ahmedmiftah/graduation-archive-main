<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

    public function up(): void
    {
        $hasForeignKey = $this->hasForeignKey('projects', 'supervisor_id');

        Schema::table('projects', function (Blueprint $table) use ($hasForeignKey) {
            if ($hasForeignKey) {
                $table->dropForeign(['supervisor_id']);
            }
            $table->dropColumn('supervisor_id');
            $table->foreignId('supervisor_id')->nullable()->after('specialization_id')->constrained('faculty_members')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['supervisor_id']);
            $table->dropColumn('supervisor_id');
            $table->foreignId('supervisor_id')->nullable()->after('specialization_id')->constrained('users')->nullOnDelete();
        });
    }
};
