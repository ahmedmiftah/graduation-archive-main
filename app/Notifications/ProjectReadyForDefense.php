<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectReadyForDefense extends Notification
{
    use Queueable;

    public function __construct(protected Project $project) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'title'      => 'المشروع جاهز للمناقشة',
            'message'    => 'أصبح مشروع "' . $this->project->project_title . '" جاهزًا للمناقشة',
            'url'        => route('projects.show', $this->project->id),
        ];
    }
}
