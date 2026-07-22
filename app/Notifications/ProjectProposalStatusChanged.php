<?php

namespace App\Notifications;

use App\Models\ProjectProposal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class ProjectProposalStatusChanged extends Notification
{
    use Queueable;

    protected $proposal;

    /**
     * Create a new notification instance.
     */
    public function __construct(ProjectProposal $proposal)
    {
        $this->proposal = $proposal;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تحديث حالة مقترح مشروعك')
            ->line('تم تغيير حالة المقترح إلى: ' . $this->proposal->status)
            ->action('عرض المقترح', url('/proposals/' . $this->proposal->id))
            ->line('شكرًا لتعاونك.');
    }

    /**
     * Store notification in database.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'proposal_id' => $this->proposal->id,
            'title' => $this->proposal->title,
            'status' => $this->proposal->status,
            'updated_at' => $this->proposal->updated_at,
        ];
    }

    /**
     * Broadcast representation.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }
}
?>
