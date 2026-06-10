<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">📋 Admission Review — Offer Admission</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif

            <div class="bg-white shadow-sm rounded-2xl overflow-hidden border">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">
                    Paid Applications ({{ $applicants->count() }})
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-100 text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Applicant</th>
                                <th class="px-4 py-3">Choices</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Decision / Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($applicants as $a)
                                <tr class="align-top">
                                    <td class="px-4 py-4">
                                        <p class="font-bold text-gray-800">{{ $a->full_name }}</p>
                                        <p class="text-xs text-gray-500">{{ $a->email }} · {{ $a->phone }}</p>
                                        @if($a->admission_number)<p class="text-xs text-indigo-600 font-semibold">{{ $a->admission_number }}</p>@endif
                                    </td>
                                    <td class="px-4 py-4 text-gray-600">
                                        <p>1. {{ $a->firstChoice->name ?? '—' }}</p>
                                        @if($a->secondChoice)<p>2. {{ $a->secondChoice->name }}</p>@endif
                                    </td>
                                    <td class="px-4 py-4">
                                        @php $map = [
                                            'submitted'=>['Awaiting decision','bg-blue-100 text-blue-700'],
                                            'admitted'=>['Offered','bg-amber-100 text-amber-700'],
                                            'accepted'=>['Accepted','bg-emerald-100 text-emerald-700'],
                                            'registered'=>['Registered','bg-green-100 text-green-700'],
                                            'rejected'=>['Declined','bg-red-100 text-red-700'],
                                            'offer_rejected'=>['Offer rejected','bg-red-100 text-red-700'],
                                        ]; $b = $map[$a->application_status] ?? [$a->application_status,'bg-gray-100 text-gray-600']; @endphp
                                        <span class="px-2 py-1 rounded-full text-xs font-bold {{ $b[1] }}">{{ $b[0] }}</span>
                                        @if($a->admittedProgram)<p class="text-xs text-gray-500 mt-1">→ {{ $a->admittedProgram->name }}</p>@endif
                                        @if($a->admission_response)<p class="text-xs text-gray-400 mt-1">Applicant: {{ ucfirst($a->admission_response) }}</p>@endif
                                    </td>
                                    <td class="px-4 py-4">
                                        @if(in_array($a->application_status, ['submitted','offer_rejected']))
                                            <form method="POST" action="{{ route('admissions.offer', $a) }}" class="flex flex-wrap gap-2 items-center">
                                                @csrf
                                                <select name="program_id" required class="border-gray-300 rounded-lg text-xs py-1">
                                                    <option value="">Admit into…</option>
                                                    @foreach($programs as $p)
                                                        <option value="{{ $p->id }}" @selected($a->first_choice_program_id==$p->id)>
                                                            {{ $p->name }} ({{ $p->department->acronym ?? '' }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button class="bg-emerald-600 text-white px-3 py-1 rounded-lg text-xs font-bold hover:bg-emerald-700">Offer</button>
                                            </form>
                                            <form method="POST" action="{{ route('admissions.decline', $a) }}" class="mt-2"
                                                  onsubmit="return confirm('Decline this application?')">
                                                @csrf
                                                <input name="reason" placeholder="Reason (optional)" class="border-gray-300 rounded-lg text-xs py-1 w-40">
                                                <button class="text-red-600 text-xs font-bold hover:underline">Decline</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No paid applications yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
