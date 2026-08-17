<?php

namespace App\Notifications;

use App\Models\ProjectIdeaRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectIdeaRequestDecided extends Notification
{
    use Queueable;

    public function __construct(protected ProjectIdeaRequest $ideaRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $accepted = $this->ideaRequest->status === ProjectIdeaRequest::STATUS_ACCEPTED;

        return [
            'idea_request_id' => $this->ideaRequest->id,
            'title'           => $accepted ? 'تم قبول طلب انضمامك' : 'تم رفض طلب انضمامك',
            'message'         => $accepted
                ? 'وافق المشرف على انضمامك لفكرة "' . $this->ideaRequest->idea->title . '"'
                : 'اعتذر المشرف عن قبول انضمامك لفكرة "' . $this->ideaRequest->idea->title . '"',
            'url'             => route('student.ideas.show', $this->ideaRequest->project_idea_id),
        ];
    }
}
