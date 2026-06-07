<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">💵 Fee Billing</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if(session('success'))
                <div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm font-medium">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">
                    <ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            @can('manage_fees')
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Single student fee -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <h3 class="font-bold text-gray-700 border-b pb-2 mb-4">Bill a Student</h3>
                    <form action="{{ route('fees.student') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Student</label>
                            <select name="student_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                                <option value="">Select student…</option>
                                @foreach($students as $s)
                                    <option value="{{ $s->id }}">{{ $s->full_name }} ({{ $s->class_arm }}) — bal ₦{{ number_format($s->fees_balance,0) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Fee Title</label>
                            <input type="text" name="title" placeholder="e.g. First Term Tuition" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Amount (₦)</label>
                            <input type="number" step="0.01" name="amount" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <button class="bg-green-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-green-700 text-sm">Bill Student</button>
                    </form>
                </div>

                <!-- Class-wide fee -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                    <h3 class="font-bold text-gray-700 border-b pb-2 mb-4">Class-wide Fee <span class="text-xs font-normal text-gray-400">(common entrance, WAEC, etc.)</span></h3>
                    <form action="{{ route('fees.class') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Fee Title</label>
                            <input type="text" name="title" placeholder="e.g. WAEC Registration" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Amount (₦) per student</label>
                            <input type="number" step="0.01" name="amount" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Apply to Classes</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-1 max-h-40 overflow-y-auto border rounded-md p-2">
                                @forelse($classes as $class)
                                    <label class="flex items-center gap-2 text-sm py-1">
                                        <input type="checkbox" name="classes[]" value="{{ $class }}" class="rounded"> {{ $class }}
                                    </label>
                                @empty
                                    <p class="text-xs text-gray-400">No classes.</p>
                                @endforelse
                            </div>
                        </div>
                        <button class="bg-indigo-600 text-white px-5 py-2 rounded-lg font-bold hover:bg-indigo-700 text-sm">Bill Selected Classes</button>
                    </form>
                </div>
            </div>
            @endcan

            <!-- Recent bills -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b"><h3 class="font-bold text-gray-700">Recent Bills</h3></div>
                <div class="p-6 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-gray-50 border-b text-xs uppercase text-gray-500">
                                <th class="p-3 font-bold">Student</th>
                                <th class="p-3 font-bold">Title</th>
                                <th class="p-3 font-bold text-right">Amount</th>
                                <th class="p-3 font-bold text-right">Paid</th>
                                <th class="p-3 font-bold text-right">Balance</th>
                                <th class="p-3 font-bold">Status</th>
                                <th class="p-3 font-bold text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentBills as $bill)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-3 font-bold">{{ $bill->student?->full_name ?? '—' }}</td>
                                <td class="p-3">{{ $bill->title }}</td>
                                <td class="p-3 text-right">₦{{ number_format($bill->amount,2) }}</td>
                                <td class="p-3 text-right text-green-600">₦{{ number_format($bill->amount_paid,2) }}</td>
                                <td class="p-3 text-right text-red-600 font-bold">₦{{ number_format($bill->balance,2) }}</td>
                                <td class="p-3">
                                    @php $badge = ['paid'=>'bg-green-100 text-green-700','part'=>'bg-yellow-100 text-yellow-700','unpaid'=>'bg-red-100 text-red-700'][$bill->status] ?? 'bg-gray-100 text-gray-600'; @endphp
                                    <span class="text-[10px] font-bold uppercase px-2 py-1 rounded {{ $badge }}">{{ $bill->status }}</span>
                                </td>
                                <td class="p-3 text-right">
                                    @can('manage_fees')
                                    @if($bill->status !== 'paid' && $bill->student)
                                        <a href="{{ route('payments.create', $bill->student_id) }}" class="bg-green-600 text-white text-xs px-3 py-1.5 rounded font-bold hover:bg-green-700">Collect</a>
                                    @endif
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="p-8 text-center text-gray-400 italic">No bills issued yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
