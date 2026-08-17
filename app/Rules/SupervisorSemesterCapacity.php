<?php

namespace App\Rules;

use App\Models\ProjectProposal;
use App\Models\SystemSetting;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Caps how many active proposals a faculty member may supervise within the
 * same academic_year + semester, per SystemSetting::max_projects_per_supervisor_per_semester.
 * Only pending/needs_revision/approved proposals count toward the limit —
 * rejected and superseded ones no longer represent real supervision load.
 */
class SupervisorSemesterCapacity implements ValidationRule
{
    public function __construct(
        private readonly ?string $academicYear,
        private readonly ?string $semester,
        private readonly ?int $excludeProposalId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value || ! $this->academicYear || ! $this->semester) {
            return;
        }

        $limit = SystemSetting::current()->max_projects_per_supervisor_per_semester;

        $count = ProjectProposal::query()
            ->where('supervisor_id', $value)
            ->where('academic_year', $this->academicYear)
            ->where('semester', $this->semester)
            ->whereNotIn('status', [ProjectProposal::STATUS_REJECTED, ProjectProposal::STATUS_SUPERSEDED])
            ->when($this->excludeProposalId, fn ($query) => $query->whereKeyNot($this->excludeProposalId))
            ->count();

        if ($count >= $limit) {
            $fail("لا يمكن تعيين هذا المشرف — لقد وصل إلى الحد الأقصى ({$limit}) لعدد المشاريع المسموح بها لكل مشرف في هذا الفصل الدراسي.");
        }
    }
}
