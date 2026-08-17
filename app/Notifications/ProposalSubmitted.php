<?php

namespace App\Notifications;

use App\Models\ProjectProposal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifies the department (dept_manager) that a proposal needs review —
 * either a brand-new submission or a resubmission replacing one that was
 * sent back for revision.
 */
class ProposalSubmitted extends Notification
{
    use Queueable;

    public function __construct(protected ProjectProposal $proposal, protected bool $isResubmission = false) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'proposal_id' => $this->proposal->id,
            'title'       => $this->isResubmission ? 'إعادة تقديم مقترح بعد التعديل' : 'مقترح جديد بحاجة مراجعة',
            'message'     => $this->isResubmission
                ? 'أعاد الطالب تقديم المقترح "' . $this->proposal->title . '" بعد التعديل المطلوب.'
                : 'تم تقديم مقترح جديد "' . $this->proposal->title . '" بانتظار مراجعة القسم.',
            'url'         => route('proposals.show', $this->proposal->id),
        ];
    }
}
