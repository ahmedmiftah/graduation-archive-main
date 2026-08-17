<?php

namespace App\Notifications;

use App\Models\ProjectIdeaRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectIdeaRequestSubmitted extends Notification
{
    use Queueable;

    public function __construct(protected ProjectIdeaRequest $ideaRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'idea_request_id' => $this->ideaRequest->id,
            'title'           => 'طلب انضمام جديد',
            'message'         => 'تقدَّم الطالب "' . $this->ideaRequest->student->full_name . '" بطلب انضمام لفكرة "' . $this->ideaRequest->idea->title . '"',
            'url'             => route('supervisor.ideas.requests.index', $this->ideaRequest->project_idea_id),
        ];
    }
}
