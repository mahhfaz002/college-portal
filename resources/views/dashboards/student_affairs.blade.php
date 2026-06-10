<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🤝 Student Affairs</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach([['Open Cases',$stats['open'],'amber'],['Resolved',$stats['resolved'],'emerald'],['Disciplinary',$stats['disciplinary'],'red'],['Total',$stats['total'],'indigo']] as [$l,$v,$c])
                    <div class="bg-white rounded-xl border p-4 border-t-4 border-{{ $c }}-500">
                        <p class="text-xs uppercase text-gray-400 font-bold">{{ $l }}</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $v }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid lg:grid-cols-3 gap-8">
                {{-- Log a case --}}
                <div class="bg-white rounded-2xl shadow-sm border p-6">
                    <h3 class="font-bold text-gray-800 mb-4">Log a Case</h3>
                    <form method="POST" action="{{ route('affairs.cases.store') }}" class="space-y-3">
                        @csrf
                        <select name="student_id" class="w-full border-gray-300 rounded-lg">
                            <option value="">Student (optional)…</option>
                            @foreach($students as $s)<option value="{{ $s->id }}">{{ $s->full_name }}</option>@endforeach
                        </select>
                        <select name="category" required class="w-full border-gray-300 rounded-lg">
                            <option value="welfare">Welfare</option>
                            <option value="disciplinary">Disciplinary</option>
                            <option value="complaint">Complaint</option>
                        </select>
                        <textarea name="description" required rows="4" placeholder="Describe the case *" class="w-full border-gray-300 rounded-lg"></textarea>
                        <button class="w-full bg-emerald-600 text-white py-2 rounded-lg font-bold hover:bg-emerald-700">Log Case</button>
                    </form>
                </div>

                {{-- Cases list --}}
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Cases</div>
                    <div class="divide-y">
                        @forelse($cases as $case)
                            <div class="p-5">
                                <div class="flex items-center justify-between gap-2">
                                    <div>
                                        <span class="text-xs font-bold uppercase px-2 py-0.5 rounded-full
                                            {{ $case->category==='disciplinary'?'bg-red-100 text-red-700':($case->category==='complaint'?'bg-amber-100 text-amber-700':'bg-indigo-100 text-indigo-700') }}">{{ $case->category }}</span>
                                        <span class="ml-2 font-semibold text-gray-800">{{ $case->student_name ?? 'General' }}</span>
                                    </div>
                                    <span class="text-xs font-bold {{ $case->status==='open'?'text-amber-600':'text-emerald-600' }}">{{ ucfirst($case->status) }}</span>
                                </div>
                                <p class="text-sm text-gray-600 mt-2">{{ $case->description }}</p>
                                <div class="mt-2 flex gap-3">
                                    @if($case->status==='open')
                                        <form method="POST" action="{{ route('affairs.cases.resolve', $case) }}">@csrf
                                            <button class="text-xs text-emerald-600 font-bold hover:underline">Mark resolved</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('affairs.cases.destroy', $case) }}" onsubmit="return confirm('Delete this case?')">@csrf @method('DELETE')
                                        <button class="text-xs text-red-500 font-bold hover:underline">Delete</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="p-6 text-center text-gray-400">No cases logged.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
