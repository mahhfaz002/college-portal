<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">⚙️ School Settings</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg">
                    <ul class="list-disc ml-5 text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Branding is set by the platform super-admin on the College record (name,
                     logo, colours, contact). It is intentionally NOT editable here so a
                     college can never change another college's identity. --}}
                <div class="bg-blue-50 border border-blue-200 text-blue-800 text-sm rounded-xl p-4">
                    Branding (college name, logo, colours, contact details &amp; domain) is managed by the
                    platform administrator. You manage your <strong>Provost block</strong> and
                    <strong>homepage key dates</strong> below — both appear only on
                    <strong>{{ $college?->name ?? 'your college' }}</strong>'s homepage.
                </div>

                {{-- Provost (per-college, shown on this college's homepage) --}}
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4 border-b pb-2">Provost / Head of College</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Provost Name</label>
                            <input name="provost_name" value="{{ old('provost_name', $college?->provost_name) }}" class="w-full rounded-lg border-gray-300" placeholder="Prof. ...">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Title</label>
                            <input name="provost_title" value="{{ old('provost_title', $college?->provost_title) }}" class="w-full rounded-lg border-gray-300" placeholder="Provost">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Welcome Message</label>
                            <textarea name="provost_message" rows="4" class="w-full rounded-lg border-gray-300" placeholder="A short welcome shown on the homepage.">{{ old('provost_message', $college?->provost_message) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Provost Picture</label>
                            <input type="file" name="provost_photo" accept="image/*" class="w-full text-sm">
                            @if($college?->provost_photo)
                                <img src="{{ $college->provost_photo }}" class="h-16 mt-2 rounded-lg object-cover">
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Key Dates (per-college, feeds the homepage "Key Dates & Timeline") --}}
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-1 border-b pb-2">Homepage Key Dates</h3>
                    <p class="text-xs text-gray-400 mb-3">Shown in the "Key Dates &amp; Timeline" section of your college homepage. Leave a row blank to skip it.</p>
                    @php $dates = $college?->key_dates ?: []; @endphp
                    <table class="w-full text-sm">
                        <thead><tr class="text-gray-400 uppercase text-xs"><th class="text-left pb-2">Event</th><th class="text-left pb-2">Date (free text)</th></tr></thead>
                        <tbody>
                            @foreach(array_pad($dates, max(count($dates), 6), ['title'=>'','date'=>'']) as $i => $row)
                                <tr>
                                    <td class="py-1 pr-2"><input name="key_dates[{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" class="w-full rounded border-gray-300" placeholder="e.g. Application Deadline"></td>
                                    <td class="py-1 pr-2"><input name="key_dates[{{ $i }}][date]" value="{{ $row['date'] ?? '' }}" class="w-full rounded border-gray-300" placeholder="e.g. 30 September 2026"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Academic --}}
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4 border-b pb-2">Academic</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Currency Symbol</label>
                            <input name="currency_symbol" value="{{ $settings['currency_symbol'] ?? '₦' }}" class="w-full rounded-lg border-gray-300">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Current Semester</label>
                            <input name="current_term" value="{{ $settings['current_term'] ?? '' }}" class="w-full rounded-lg border-gray-300">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Current Session</label>
                            <input name="current_session" value="{{ $settings['current_session'] ?? '' }}" class="w-full rounded-lg border-gray-300">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Max CA Score</label>
                            <input type="number" name="ca_max_score" value="{{ $settings['ca_max_score'] ?? 40 }}" class="w-full rounded-lg border-gray-300">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Max Exam Score</label>
                            <input type="number" name="exam_max_score" value="{{ $settings['exam_max_score'] ?? 60 }}" class="w-full rounded-lg border-gray-300">
                        </div>
                    </div>
                </div>

                {{-- Grading scheme --}}
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4 border-b pb-2">Grading Scheme</h3>
                    @php $scheme = json_decode($settings['grading_scheme'] ?? '[]', true) ?: []; @endphp
                    <table class="w-full text-sm" id="grades">
                        <thead><tr class="text-gray-400 uppercase text-xs"><th class="text-left pb-2">Min %</th><th class="text-left pb-2">Grade</th><th class="text-left pb-2">Remark</th></tr></thead>
                        <tbody>
                            @foreach(array_pad($scheme, max(count($scheme), 6), ['min'=>'','grade'=>'','remark'=>'']) as $i => $row)
                                <tr>
                                    <td class="py-1 pr-2"><input type="number" name="grades[{{ $i }}][min]" value="{{ $row['min'] ?? '' }}" class="w-24 rounded border-gray-300"></td>
                                    <td class="py-1 pr-2"><input name="grades[{{ $i }}][grade]" value="{{ $row['grade'] ?? '' }}" class="w-20 rounded border-gray-300"></td>
                                    <td class="py-1 pr-2"><input name="grades[{{ $i }}][remark]" value="{{ $row['remark'] ?? '' }}" class="w-full rounded border-gray-300"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="text-xs text-gray-400 mt-2">A score at or above each "Min %" earns that grade. Leave a grade blank to ignore the row.</p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="text-white px-6 py-2 rounded-lg font-bold shadow-sm" style="background: var(--brand)">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
