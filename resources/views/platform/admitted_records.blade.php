<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🎓 Admitted Student Records</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            <div class="bg-white p-6 rounded-2xl shadow-sm border">
                <h3 class="font-bold text-gray-800 mb-1">Upload admitted students (CSV)</h3>
                <p class="text-sm text-gray-500 mb-4">
                    The CSV must have a header row with these columns (any order):
                    <code class="bg-gray-100 px-1 rounded">Full Name</code>,
                    <code class="bg-gray-100 px-1 rounded">Registration Number</code>,
                    <code class="bg-gray-100 px-1 rounded">Department</code>,
                    <code class="bg-gray-100 px-1 rounded">Level</code>.
                    Re-uploading updates existing rows (matched by registration number). Students then create
                    their own account by entering their registration number.
                </p>

                <form method="POST" action="{{ route('platform.admitted-records.upload') }}" enctype="multipart/form-data" class="grid sm:grid-cols-3 gap-3 items-end">
                    @csrf
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">College</label>
                        <select name="college_id" required class="w-full border-gray-300 rounded-lg text-sm">
                            <option value="">— Select college —</option>
                            @foreach($colleges as $c)<option value="{{ $c->id }}">{{ $c->name }} ({{ $c->acronym }})</option>@endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">CSV file</label>
                        <input type="file" name="csv" accept=".csv,text/csv" required class="block w-full text-sm">
                    </div>
                    <button class="bg-emerald-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-emerald-700 text-sm">Import</button>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-3 bg-gray-50 border-b"><h3 class="font-bold text-gray-700">Records per college</h3></div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-400">
                        <tr><th class="px-6 py-2 text-left">College</th><th class="px-4 py-2 text-right">Total</th><th class="px-4 py-2 text-right">Claimed</th><th class="px-4 py-2 text-right">Available</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($colleges as $c)
                            @php $row = $counts->get($c->id); $total = (int) ($row->total ?? 0); $claimed = (int) ($row->claimed ?? 0); @endphp
                            <tr>
                                <td class="px-6 py-2 font-semibold text-gray-700">{{ $c->name }}</td>
                                <td class="px-4 py-2 text-right">{{ $total }}</td>
                                <td class="px-4 py-2 text-right text-gray-500">{{ $claimed }}</td>
                                <td class="px-4 py-2 text-right font-bold text-emerald-700">{{ $total - $claimed }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
