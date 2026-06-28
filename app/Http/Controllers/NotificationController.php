<?php

namespace App\Http\Controllers;

use App\Support\Notifications;

class NotificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $items = Notifications::feedFor($user);

        // Opening the page marks everything seen, so the bell badge clears to 0.
        // Guarded so a deploy that hasn't run the migration yet can't 500.
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'notifications_last_read_at')) {
            $user->forceFill(['notifications_last_read_at' => now()])->save();
        }

        return view('notifications.index', compact('items'));
    }
}
