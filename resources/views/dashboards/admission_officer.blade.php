<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🎟️ Admission Officer</h2>
    </x-slot>

    <div class="py-10" x-data="{ tab: 'queue' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif

            <div class="grid grid-cols-3 gap-4">
                <button @click="tab='queue'" :class="tab==='queue'?'ring-2 ring-amber-400':''" class="bg-white rounded-xl border p-4 border-t-4 border-amber-500 text-left">
                    <p class="text-xs uppercase text-gray-400 font-bold">Awaiting Decision</p><p class="text-2xl font-bold text-gray-800">{{ $stats['queue'] }}</p>
                </button>
                <button @click="tab='accepted'" :class="tab==='accepted'?'ring-2 ring-emerald-400':''" class="bg-white rounded-xl border p-4 border-t-4 border-emerald-500 text-left">
                    <p class="text-xs uppercase text-gray-400 font-bold">Admitted</p><p class="text-2xl font-bold text-gray-800">{{ $stats['accepted'] }}</p>
                </button>
                <button @click="tab='rejected'" :class="tab==='rejected'?'ring-2 ring-red-400':''" class="bg-white rounded-xl border p-4 border-t-4 border-red-500 text-left">
                    <p class="text-xs uppercase text-gray-400 font-bold">Rejected</p><p class="text-2xl font-bold text-gray-800">{{ $stats['rejected'] }}</p>
                </button>
            </div>

            {{-- Queue --}}
            <div x-show="tab==='queue'" class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Applicant Queue — Approve / Reject</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                        <th class="px-4 py-2 text-left">Applicant</th><th class="px-4 py-2 text-left">Choices</th><th class="px-4 py-2 text-left">O'Level</th><th class="px-4 py-2 text-right">Decision</th>
                    </tr></thead>
                    <tbody class="divide-y">
                        @forelse($queue as $a)
                            <tr class="align-top">
                                <td class="px-4 py-3"><p class="font-bold text-gray-800">{{ $a->full_name }}</p><p class="text-xs text-gray-500">{{ $a->email }}</p></td>
                                <td class="px-4 py-3 text-gray-600"><p>1. {{ $a->firstChoice->name ?? '—' }}</p>@if($a->secondChoice)<p>2. {{ $a->secondChoice->name }}</p>@endif</td>
                                <td class="px-4 py-3 text-xs text-gray-500">
                                    {{ $a->exam_type }} {{ $a->exam_year }}
                                    @if(is_array($a->olevel_results))<br>{{ collect($a->olevel_results)->map(fn($r)=>($r['subject']??'').' '.($r['grade']??''))->implode(', ') }}@endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('admissions.offer', $a) }}" class="flex gap-1 justify-end items-center">
                                        @csrf
                                        <select name="program_id" required class="border-gray-300 rounded text-xs py-1">
                                            @foreach($programs as $p)<option value="{{ $p->id }}" @selected($a->first_choice_program_id==$p->id)>{{ $p->name }}</option>@endforeach
                                        </select>
                                        <button class="bg-emerald-600 text-white px-3 py-1 rounded text-xs font-bold">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admissions.decline', $a) }}" class="mt-1" onsubmit="return confirm('Reject this application?')">
                                        @csrf<button class="text-red-600 text-xs font-bold hover:underline">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No applicants awaiting a decision.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Accepted --}}
            <div x-show="tab==='accepted'" x-cloak class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Admitted / Accepted</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-4 py-2 text-left">Applicant</th><th class="px-4 py-2 text-left">Admitted To</th><th class="px-4 py-2 text-left">Status</th><th class="px-4 py-2 text-left">Adm. No</th></tr></thead>
                    <tbody class="divide-y">
                        @forelse($accepted as $a)
                            <tr><td class="px-4 py-2 font-semibold">{{ $a->full_name }}</td><td class="px-4 py-2">{{ $a->admittedProgram->name ?? '—' }}</td><td class="px-4 py-2">{{ ucfirst($a->application_status) }}</td><td class="px-4 py-2 text-indigo-600">{{ $a->admission_number }}</td></tr>
                        @empty<tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">None yet.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>

            {{-- Rejected --}}
            <div x-show="tab==='rejected'" x-cloak class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Rejected</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-4 py-2 text-left">Applicant</th><th class="px-4 py-2 text-left">Choice</th><th class="px-4 py-2 text-left">Reason</th></tr></thead>
                    <tbody class="divide-y">
                        @forelse($rejected as $a)
                            <tr><td class="px-4 py-2 font-semibold">{{ $a->full_name }}</td><td class="px-4 py-2">{{ $a->firstChoice->name ?? '—' }}</td><td class="px-4 py-2 text-gray-500">{{ $a->reason }}</td></tr>
                        @empty<tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">None.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
