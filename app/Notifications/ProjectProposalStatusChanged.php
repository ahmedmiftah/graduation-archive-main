<?php

namespace App\Notifications;

use App\Models\ProjectProposal;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\AnonymousNotifiable;
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
        // Faculty-member recipients are routed on-demand (no login account,
        // so no notifications table row to write and no session to broadcast to).
        if ($notifiable instanceof AnonymousNotifiable) {
            return ['mail'];
        }

        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $labels = [
            'pending'        => 'مبدئي',
            'needs_revision' => 'مقبول بشرط التعديل',
            'rejected'       => 'مرفوض نهائياً',
            'approved'       => 'معتمد',
            'superseded'     => 'مستبدَل بمقترح جديد',
        ];

        $message = (new MailMessage)
            ->subject('تحديث حالة مقترح مشروعك')
            ->line('تم تغيير حالة المقترح "' . $this->proposal->title . '" إلى: ' . ($labels[$this->proposal->status] ?? $this->proposal->status));

        if ($this->proposal->status === 'rejected' && $this->proposal->rejection_reason) {
            $message->line('سبب الرفض: ' . $this->proposal->rejection_reason);
        }

        return $message->action('عرض المقترح', $this->urlFor($notifiable))
            ->line('شكرًا لتعاونك.');
    }

    /**
     * Store notification in database.
     */
    public function toDatabase(object $notifiable): array
    {
        $labels = [
            'pending'        => 'مبدئي',
            'needs_revision' => 'مقبول بشرط التعديل',
            'rejected'       => 'مرفوض نهائياً',
            'approved'       => 'معتمد',
            'superseded'     => 'مستبدَل بمقترح جديد',
        ];

        return [
            'proposal_id' => $this->proposal->id,
            'title'       => 'تحديث حالة المقترح',
            'message'     => 'تم تغيير حالة المقترح "' . $this->proposal->title . '" إلى: ' . ($labels[$this->proposal->status] ?? $this->proposal->status),
            'status'      => $this->proposal->status,
            'url'         => $this->urlFor($notifiable),
        ];
    }

    /**
     * Broadcast representation.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toDatabase($notifiable));
    }

    /**
     * The proposal detail page differs by recipient role.
     */
    private function urlFor(object $notifiable): string
    {
        if ($notifiable instanceof User && $notifiable->hasRole('student')) {
            return route('student.proposal.show', $this->proposal->id);
        }

        if ($notifiable instanceof User && $notifiable->hasRole('supervisor')) {
            return route('supervisor.proposals.show', $this->proposal->id);
        }

        return route('proposals.show', $this->proposal->id);
    }
}
