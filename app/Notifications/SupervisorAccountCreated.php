<?php

namespace App\Notifications;

use App\Models\FacultyMember;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupervisorAccountCreated extends Notification
{
    use Queueable;

    public function __construct(protected FacultyMember $facultyMember) {}

    /**
     * Unlike students, faculty members already have a real email address —
     * mail delivery is meaningful here.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تم إنشاء حساب الدخول الخاص بك')
            ->line('مرحباً ' . $this->facultyMember->full_name . '، تم إنشاء حساب دخول لك في نظام أرشفة مشاريع التخرج.')
            ->line('اسم الدخول هو بريدك الإلكتروني المسجَّل، وكلمة المرور المؤقتة هي رقم جوالك المسجَّل — سيُطلب منك تغييرها عند أول دخول.')
            ->action('تسجيل الدخول', url('/login'));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'faculty_member_id' => $this->facultyMember->id,
            'title'             => 'تم إنشاء حسابك في نظام أرشفة مشاريع التخرج',
            'message'           => 'مرحباً ' . $this->facultyMember->full_name . '، تم إنشاء حسابك. اسم الدخول هو بريدك الإلكتروني المسجَّل.',
            'url'               => route('supervisor.dashboard'),
        ];
    }
}
