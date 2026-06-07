<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SupportTicketController extends Controller
{
    /**
     * ICT (and proprietor oversight) see all tickets; everyone else sees
     * their own.
     */
    public function index()
    {
        $user = auth()->user();
        $canHandle = $user->canManage('handle_tickets') || $user->isReadOnly();

        $tickets = SupportTicket::with('user', 'handler')
            ->when(!$canHandle, fn ($q) => $q->where('user_id', $user->id))
            ->latest()
            ->get();

        return view('support.index', compact('tickets', 'canHandle'));
    }

    /** Any authenticated user can report a challenge. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'subject'  => 'required|string|max:255',
            'body'     => 'required|string',
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high'])],
        ]);

        SupportTicket::create([
            'user_id'  => auth()->id(),
            'subject'  => $data['subject'],
            'body'     => $data['body'],
            'priority' => $data['priority'] ?? 'normal',
            'status'   => 'open',
        ]);

        return back()->with('success', 'Support ticket submitted. ICT will respond shortly.');
    }

    /** ICT responds / resolves a ticket. */
    public function update(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'response' => 'nullable|string',
            'status'   => ['required', Rule::in(['open', 'in_progress', 'resolved'])],
        ]);

        $ticket->update([
            'response'   => $data['response'] ?? $ticket->response,
            'status'     => $data['status'],
            'handled_by' => auth()->id(),
        ]);

        return back()->with('success', 'Ticket updated.');
    }

    /**
     * ICT password reset: issues a temp password the user must change.
     */
    public function resetPassword(User $user)
    {
        $temp = Str::random(8);
        $user->update([
            'password' => Hash::make($temp),
            'must_change_password' => true,
        ]);

        return back()->with('success', "Password reset for {$user->name}. Temp password: {$temp}");
    }
}
