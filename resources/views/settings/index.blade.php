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

                {{-- Branding --}}
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4 border-b pb-2">Branding</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">School Name</label>
                            <input name="school_name" value="{{ $settings['school_name'] ?? '' }}" class="w-full rounded-lg border-gray-300" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tagline</label>
                            <input name="school_tagline" value="{{ $settings['school_tagline'] ?? '' }}" class="w-full rounded-lg border-gray-300">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Primary Color</label>
                            <input type="color" name="primary_color" value="{{ $settings['primary_color'] ?? '#2563eb' }}" class="h-10 w-20 rounded border-gray-300">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Logo</label>
                            <input type="file" name="logo" accept="image/*" class="w-full text-sm">
                            @if(!empty($settings['school_logo']))
                                <img src="{{ media_url($settings['school_logo']) }}" class="h-12 mt-2 rounded">
                            @endif
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Address</label>
                            <input name="school_address" value="{{ $settings['school_address'] ?? '' }}" class="w-full rounded-lg border-gray-300">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Phone</label>
                            <input name="school_phone" value="{{ $settings['school_phone'] ?? '' }}" class="w-full rounded-lg border-gray-300">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Public Email</label>
                            <input name="school_email" value="{{ $settings['school_email'] ?? '' }}" class="w-full rounded-lg border-gray-300">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Staff Email Domain</label>
                            <input name="staff_email_domain" value="{{ $settings['staff_email_domain'] ?? '' }}" placeholder="excellence.edu" class="w-full rounded-lg border-gray-300">
                        </div>
                    </div>
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
