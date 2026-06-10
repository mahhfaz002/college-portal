<?php

namespace App\Http\Controllers;

use App\Models\Correspondence;
use Illuminate\Http\Request;

/**
 * Office Secretary — correspondence register (incoming/outgoing letters & memos).
 */
class OfficeSecretaryController extends Controller
{
    public function dashboard(Request $request)
    {
        $items = Correspondence::latest()->get();

        $stats = [
            'incoming' => $items->where('direction', 'incoming')->count(),
            'outgoing' => $items->where('direction', 'outgoing')->count(),
            'pending'  => $items->where('status', 'received')->count(),
            'total'    => $items->count(),
        ];

        return view('dashboards.office_secretary', compact('items', 'stats'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ref_no'    => 'nullable|string|max:80',
            'direction' => 'required|in:incoming,outgoing',
            'subject'   => 'required|string|max:200',
            'party'     => 'nullable|string|max:200',
            'dated'     => 'nullable|date',
            'notes'     => 'nullable|string|max:255',
        ]);
        $data['college_id'] = current_college_id();
        $data['status'] = 'received';
        Correspondence::create($data);

        return back()->with('success', 'Correspondence logged.');
    }

    public function updateStatus(Request $request, Correspondence $correspondence)
    {
        $data = $request->validate(['status' => 'required|in:received,filed,forwarded']);
        $correspondence->update($data);

        return back()->with('success', 'Status updated.');
    }

    public function destroy(Correspondence $correspondence)
    {
        $correspondence->delete();
        return back()->with('success', 'Entry removed.');
    }
}
