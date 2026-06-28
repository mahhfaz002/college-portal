<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🔁 Change of Course — HOD Review</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            {{-- Incoming: students requesting to join MY department --}}
            <div>
                <h3 class="font-bold text-gray-700 mb-3">Requests to join my department ({{ $incoming->count() }})</h3>
                @forelse($incoming as $r)
                    @include('change_of_course._hod_card', ['r' => $r, 'context' => 'incoming'])
                @empty
                    <div class="bg-white rounded-2xl shadow-sm border p-8 text-center text-gray-400">No incoming transfer requests.</div>
                @endforelse
            </div>

            {{-- Outgoing: my students requesting to leave --}}
            <div>
                <h3 class="font-bold text-gray-700 mb-3">My students requesting to transfer out ({{ $outgoing->count() }})</h3>
                @forelse($outgoing as $r)
                    @include('change_of_course._hod_card', ['r' => $r, 'context' => 'outgoing'])
                @empty
                    <div class="bg-white rounded-2xl shadow-sm border p-8 text-center text-gray-400">No outgoing transfer requests.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
