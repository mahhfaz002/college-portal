<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReportCardGenerated extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

/**
 * Get the mail representation of the notification.
 */
public function toMail($notifiable)
{
    return (new MailMessage)
        ->subject('Your Statement of Result is Ready!')
        ->greeting('Hello!')
        ->line('Your statement of result for the First Semester 2025/2026 has been generated.')
        ->line('You can log in to the student portal to view and download your results.')
        ->action('View Statement of Result', route('dashboard')) // Links to student dashboard
        ->line('Thank you for choosing our college!');
}

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
