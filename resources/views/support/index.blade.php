<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🛠️ Technical Support</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            <!-- Raise a ticket (proprietor is read-only, so hide for them) -->
            @unless(auth()->user()->isReadOnly())
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="font-bold text-gray-700 border-b pb-2 mb-4">Report a Challenge</h3>
                <form action="{{ route('support.store') }}" method="POST" class="space-y-3">
                    @csrf
                    <input name="subject" placeholder="Subject" class="w-full border-gray-300 rounded-md shadow-sm" required>
                    <textarea name="body" rows="3" placeholder="Describe the issue…" class="w-full border-gray-300 rounded-md shadow-sm" required></textarea>
                    <div class="flex items-center justify-between">
                        <select name="priority" class="border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="low">Low</option>
                            <option value="normal" selected>Normal</option>
                            <option value="high">High</option>
                        </select>
                        <button class="bg-indigo-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-indigo-700 text-sm">Submit Ticket</button>
                    </div>
                </form>
            </div>
            @endunless

            <!-- Tickets -->
            <div class="space-y-3">
                @forelse($tickets as $ticket)
                <div class="bg-white p-5 rounded-xl shadow-sm border {{ $ticket->status==='resolved' ? 'border-gray-200' : 'border-yellow-300' }}">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-bold text-gray-800">{{ $ticket->subject }}
                                @php $pc = ['high'=>'bg-red-100 text-red-700','normal'=>'bg-gray-100 text-gray-600','low'=>'bg-blue-100 text-blue-700'][$ticket->priority] ?? 'bg-gray-100'; @endphp
                                <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded {{ $pc }}">{{ $ticket->priority }}</span>
                            </p>
                            <p class="text-sm text-gray-600 mt-1">{{ $ticket->body }}</p>
                            <p class="text-xs text-gray-400 mt-1">by {{ $ticket->user->name ?? '—' }} · {{ $ticket->created_at->diffForHumans() }}</p>
                        </div>
                        @php $sc = ['open'=>'bg-yellow-100 text-yellow-700','in_progress'=>'bg-blue-100 text-blue-700','resolved'=>'bg-green-100 text-green-700'][$ticket->status] ?? 'bg-gray-100'; @endphp
                        <span class="text-[10px] uppercase font-bold px-2 py-1 rounded {{ $sc }}">{{ str_replace('_',' ',$ticket->status) }}</span>
                    </div>

                    @if($ticket->response)
                        <p class="mt-3 text-sm text-green-800 bg-green-50 border border-green-200 rounded p-2"><strong>ICT:</strong> {{ $ticket->response }}</p>
                    @endif

                    @if($canHandle && auth()->user()->canManage('handle_tickets'))
                    <form action="{{ route('support.update', $ticket) }}" method="POST" class="mt-3 border-t pt-3 space-y-2">
                        @csrf @method('PUT')
                        <textarea name="response" rows="2" placeholder="Response…" class="w-full border-gray-300 rounded-md text-sm">{{ $ticket->response }}</textarea>
                        <div class="flex items-center gap-2">
                            <select name="status" class="border-gray-300 rounded-md text-sm">
                                <option value="open" {{ $ticket->status==='open'?'selected':'' }}>Open</option>
                                <option value="in_progress" {{ $ticket->status==='in_progress'?'selected':'' }}>In progress</option>
                                <option value="resolved" {{ $ticket->status==='resolved'?'selected':'' }}>Resolved</option>
                            </select>
                            <button class="bg-indigo-600 text-white px-4 py-1.5 rounded-lg font-bold hover:bg-indigo-700 text-sm">Update</button>
                        </div>
                    </form>
                    @endif
                </div>
                @empty
                <div class="bg-white p-8 rounded-xl border text-center text-gray-400 italic">No tickets yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
