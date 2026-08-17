<?php

namespace App\Notifications;

use App\Models\ProjectFeedback;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class NewProjectFeedbackNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly ProjectFeedback $feedback) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'feedback_id'     => $this->feedback->id,
            'project_title'   => $this->feedback->project_title,
            'visitor_name'    => $this->feedback->visitor_name,
            'feedback_type'   => $this->feedback->feedback_type,
            'title'           => $this->feedback->title,
            'message'         => 'ملاحظة جديدة على مشروع "' . $this->feedback->project_title . '" من ' . $this->feedback->visitor_name,
            'rating'          => $this->feedback->rating,
            'created_at'      => $this->feedback->created_at->toDateTimeString(),
            'excerpt'         => str($this->feedback->message)->limit(120),
            'url'             => route('feedback.show', ['feedback' => $this->feedback->id]),
        ];
    }
}
