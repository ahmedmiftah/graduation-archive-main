<?php

namespace App\Notifications;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StudentAccountCreated extends Notification
{
    use Queueable;

    public function __construct(protected Student $student) {}

    /**
     * Students authenticate with a synthetic internal email (no real inbox),
     * so mail delivery is meaningless here — database channel only.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'student_id' => $this->student->id,
            'title'      => 'تم إنشاء حسابك في نظام أرشفة مشاريع التخرج',
            'message'    => 'مرحباً ' . $this->student->full_name . '، تم إنشاء حسابك. اسم الدخول هو رقم القيد الخاص بك.',
            'url'        => route('student.dashboard'),
        ];
    }
}
