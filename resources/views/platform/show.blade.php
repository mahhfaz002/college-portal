<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🏫 {{ $college->name }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
            <a href="{{ route('platform.colleges') }}" class="text-sm text-indigo-600 hover:underline">← All colleges</a>

            <div class="grid sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Students</p><p class="text-2xl font-black text-gray-800">{{ number_format($students) }}</p></div>
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Staff</p><p class="text-2xl font-black text-gray-800">{{ number_format($staff) }}</p></div>
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Applicants</p><p class="text-2xl font-black text-gray-800">{{ number_format($applicants) }}</p></div>
                <div class="bg-white rounded-xl border p-4"><p class="text-xs uppercase text-gray-400 font-bold">Revenue</p><p class="text-2xl font-black text-emerald-600">{{ money($revenue) }}</p></div>
            </div>

            {{-- Leadership accounts --}}
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Leadership Accounts</div>
                <table class="w-full text-sm">
                    <tbody class="divide-y">
                        @forelse($admins as $a)
                            <tr>
                                <td class="px-6 py-3 font-semibold text-gray-800">{{ $a->name }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ $a->email }}</td>
                                <td class="px-6 py-3"><span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-bold">{{ str_replace('_',' ',$a->role) }}</span></td>
                                <td class="px-6 py-3 text-right whitespace-nowrap">
                                    <form method="POST" action="{{ route('platform.colleges.admins.reset', [$college, $a]) }}" class="inline" onsubmit="return confirm('Reset this admin password? A new temporary password will be shown.')">
                                        @csrf<button class="text-indigo-600 text-xs font-bold hover:underline">Reset password</button>
                                    </form>
                                    <form method="POST" action="{{ route('platform.colleges.admins.remove', [$college, $a]) }}" class="inline ml-3" onsubmit="return confirm('Remove this admin account?')">
                                        @csrf @method('DELETE')<button class="text-red-500 text-xs font-bold hover:underline">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-4 text-center text-gray-400">No leadership accounts yet — create them below.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <form method="POST" action="{{ route('platform.colleges.admins.add', $college) }}" class="p-6 border-t flex flex-wrap gap-2 items-end bg-gray-50">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Role</label>
                        <select name="role" required class="border-gray-300 rounded-lg text-sm">
                            @foreach($adminRoles as $r)<option value="{{ $r }}">{{ ucwords(str_replace('_',' ',$r)) }}</option>@endforeach
                        </select>
                    </div>
                    <input name="name" required placeholder="Full name *" class="border-gray-300 rounded-lg text-sm">
                    <input name="email" type="email" required placeholder="Email *" class="border-gray-300 rounded-lg text-sm">
                    <input name="password" required placeholder="Temp password *" class="border-gray-300 rounded-lg text-sm">
                    <button class="bg-emerald-600 text-white px-5 py-2 rounded-lg text-sm font-bold hover:bg-emerald-700">Add Admin</button>
                </form>
            </div>

            {{-- Edit college branding / domain --}}
            <div class="bg-white rounded-2xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-800">College Details &amp; Branding</h3>
                    <form method="POST" action="{{ route('platform.colleges.toggle', $college) }}">@csrf
                        <button class="px-4 py-1.5 rounded-lg text-xs font-bold {{ $college->is_active ? 'bg-red-100 text-red-700 hover:bg-red-200':'bg-green-100 text-green-700 hover:bg-green-200' }}">{{ $college->is_active ? 'Suspend' : 'Reactivate' }}</button>
                    </form>
                </div>
                <form method="POST" action="{{ route('platform.colleges.update', $college) }}" class="space-y-3">
                    @csrf @method('PUT')
                    <div class="grid md:grid-cols-2 gap-3">
                        <input name="name" value="{{ $college->name }}" required placeholder="Name" class="border-gray-300 rounded-lg md:col-span-2">
                        <input name="acronym" value="{{ $college->acronym }}" required placeholder="Acronym" class="border-gray-300 rounded-lg">
                        <input name="domain" value="{{ $college->domain }}" placeholder="Domain (e.g. albazchst.edu.ng)" class="border-gray-300 rounded-lg">
                        <input name="email" value="{{ $college->email }}" placeholder="Email" class="border-gray-300 rounded-lg">
                        <input name="phone" value="{{ $college->phone }}" placeholder="Phone" class="border-gray-300 rounded-lg">
                        <input name="address" value="{{ $college->address }}" placeholder="Address" class="border-gray-300 rounded-lg md:col-span-2">
                        <input name="tagline" value="{{ $college->tagline }}" placeholder="Tagline" class="border-gray-300 rounded-lg">
                        <input name="motto" value="{{ $college->motto }}" placeholder="Motto" class="border-gray-300 rounded-lg">
                        <textarea name="about" placeholder="About the college (homepage)" class="border-gray-300 rounded-lg md:col-span-2" rows="2">{{ $college->about }}</textarea>
                        <input name="provost_name" value="{{ $college->provost_name }}" placeholder="Provost name" class="border-gray-300 rounded-lg">
                        <input name="primary_color" type="color" value="{{ $college->primary_color ?? '#1d4ed8' }}" class="h-10 w-20 border-gray-300 rounded">
                        <textarea name="provost_message" placeholder="Provost welcome message (homepage)" class="border-gray-300 rounded-lg md:col-span-2" rows="2">{{ $college->provost_message }}</textarea>
                    </div>
                    <button class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-700">Save Details</button>
                </form>
            </div>

            {{-- Danger zone --}}
            <div class="bg-white rounded-2xl border border-red-200 p-6">
                <h3 class="font-bold text-red-700 mb-1">Danger Zone</h3>
                <p class="text-sm text-gray-500 mb-3">Permanently delete this college and <strong>all</strong> its students, staff, applications and finance records. This cannot be undone.</p>
                <form method="POST" action="{{ route('platform.colleges.destroy', $college) }}" onsubmit="return confirm('Permanently delete {{ $college->name }} and ALL its data?')">
                    @csrf @method('DELETE')
                    <button class="bg-red-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-red-700">Delete College</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
