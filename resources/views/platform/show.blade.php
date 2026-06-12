<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🏫 {{ $college->name }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            <a href="{{ route('platform.colleges') }}" class="text-sm text-indigo-600 hover:underline">← All colleges</a>

            <div class="grid sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Students</p><p class="text-2xl font-black text-gray-800">{{ number_format($students) }}</p></div>
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Staff</p><p class="text-2xl font-black text-gray-800">{{ number_format($staff) }}</p></div>
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Applicants</p><p class="text-2xl font-black text-gray-800">{{ number_format($applicants) }}</p></div>
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Revenue</p><p class="text-2xl font-black text-emerald-600">{{ money($revenue) }}</p></div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-800">Tenant Details</h3>
                    <form method="POST" action="{{ route('platform.colleges.toggle', $college) }}" onsubmit="return confirm('{{ $college->is_active ? 'Suspend' : 'Reactivate' }} this college?')">
                        @csrf
                        <button class="px-4 py-1.5 rounded-lg text-xs font-bold {{ $college->is_active ? 'bg-red-100 text-red-700 hover:bg-red-200':'bg-green-100 text-green-700 hover:bg-green-200' }}">{{ $college->is_active ? 'Suspend' : 'Reactivate' }}</button>
                    </form>
                </div>
                <div class="grid sm:grid-cols-2 gap-x-8 gap-y-2 text-sm">
                    <div><span class="text-gray-400">Acronym:</span> <span class="font-semibold">{{ $college->acronym }}</span></div>
                    <div><span class="text-gray-400">Domain:</span> <span class="font-semibold">{{ $college->domain ?? '— (shared address)' }}</span></div>
                    <div><span class="text-gray-400">Email:</span> <span class="font-semibold">{{ $college->email ?? '—' }}</span></div>
                    <div><span class="text-gray-400">Phone:</span> <span class="font-semibold">{{ $college->phone ?? '—' }}</span></div>
                    <div><span class="text-gray-400">Reg. format:</span> <span class="font-semibold">{{ $college->registration_no_format }}</span></div>
                    <div><span class="text-gray-400">Paystack:</span> <span class="font-semibold">{{ $college->paystack_secret_key ? 'Own keys set' : 'Platform default' }}</span></div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Administrators</div>
                <table class="w-full text-sm">
                    <tbody class="divide-y">
                        @forelse($admins as $a)
                            <tr><td class="px-6 py-3 font-semibold text-gray-800">{{ $a->name }}</td><td class="px-6 py-3 text-gray-500">{{ $a->email }}</td><td class="px-6 py-3"><span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-bold">{{ $a->role }}</span></td></tr>
                        @empty
                            <tr><td class="px-6 py-4 text-center text-gray-400">No admin accounts.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
