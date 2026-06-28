<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Tells a student their carryover result has been merged into the original
 * semester's record after a results transmission. Delivered to the database
 * (surfaced in the in-app notifications feed) — no mail dependency.
 */
class CarryoverResultUpdated extends Notification
{
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'icon'    => '🔁',
            'title'   => 'Carryover results updated',
            'message' => 'Your carryover course results have just been processed. Please check your results to confirm your carryover courses are now reflected. Any course still showing F will be re-registered for you next corresponding semester.',
            'url'     => route('results.student.index'),
        ];
    }
}
