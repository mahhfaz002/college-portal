<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">👩‍🏫 Resource Persons — {{ $department->name ?? 'Department' }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            <div class="grid lg:grid-cols-2 gap-8">
                {{-- Register form --}}
                <div class="bg-white rounded-2xl shadow-sm border p-6">
                    <h3 class="font-bold text-gray-800 mb-4">Register a Resource Person</h3>
                    <form method="POST" action="{{ route('hod.resource-persons.store') }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div class="grid sm:grid-cols-2 gap-3">
                            <input name="first_name" value="{{ old('first_name') }}" required placeholder="First name *" class="border-gray-300 rounded-lg">
                            <input name="other_name" value="{{ old('other_name') }}" placeholder="Other name" class="border-gray-300 rounded-lg">
                            <input name="surname" value="{{ old('surname') }}" required placeholder="Surname *" class="border-gray-300 rounded-lg">
                            <input name="phone" value="{{ old('phone') }}" required placeholder="Phone *" class="border-gray-300 rounded-lg">
                        </div>
                        <input name="email" type="email" value="{{ old('email') }}" required placeholder="Email address *" class="w-full border-gray-300 rounded-lg">
                        <input name="address" value="{{ old('address') }}" placeholder="Address" class="w-full border-gray-300 rounded-lg">
                        <div class="grid sm:grid-cols-2 gap-3">
                            <input name="qualification" value="{{ old('qualification') }}" required placeholder="Qualification * (e.g. M.Sc Microbiology)" class="border-gray-300 rounded-lg">
                            <input name="class_of_degree" value="{{ old('class_of_degree') }}" placeholder="Class of degree (e.g. First Class)" class="border-gray-300 rounded-lg">
                        </div>
                        <input name="university" value="{{ old('university') }}" required placeholder="University graduated from *" class="w-full border-gray-300 rounded-lg">
                        <div class="grid sm:grid-cols-2 gap-3">
                            <input name="temp_password" required placeholder="Temporary password *" class="border-gray-300 rounded-lg">
                            <input name="passport" type="file" accept="image/*" class="text-sm text-gray-600">
                        </div>
                        <p class="text-xs text-gray-400">Username is auto-generated. The resource person is prompted to change the temporary password on first login.</p>
                        <button class="bg-emerald-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-emerald-700">Create Account</button>
                    </form>
                </div>

                {{-- List --}}
                <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Department Lecturers ({{ $lecturers->count() }})</div>
                    <div class="divide-y">
                        @forelse($lecturers as $l)
                            <div class="px-6 py-4" x-data="{ edit: false }">
                                <div class="flex justify-between items-start gap-3">
                                    <div class="min-w-0">
                                        <p class="font-bold text-gray-800">{{ $l->name }}</p>
                                        <p class="text-xs text-gray-500 break-words">{{ $l->username }} · {{ $l->email }} · {{ $l->qualification }} ({{ $l->university }})</p>
                                    </div>
                                    <div class="flex gap-1 shrink-0">
                                        <button type="button" @click="edit = !edit" class="text-xs font-bold text-indigo-600 hover:underline px-2 py-1">Edit</button>
                                        <form method="POST" action="{{ route('hod.resource-persons.destroy', $l) }}"
                                              onsubmit="return confirm('Remove {{ $l->name }}? This permanently deletes their account.')">
                                            @csrf @method('DELETE')
                                            <button class="text-xs font-bold text-red-600 hover:underline px-2 py-1">Delete</button>
                                        </form>
                                    </div>
                                </div>
                                <form x-show="edit" x-cloak method="POST" action="{{ route('hod.resource-persons.update', $l) }}"
                                      class="mt-3 grid sm:grid-cols-2 gap-2 bg-gray-50 p-3 rounded-lg">
                                    @csrf @method('PUT')
                                    <input name="first_name" value="{{ $l->first_name }}" required placeholder="First name" class="border-gray-300 rounded text-sm">
                                    <input name="surname" value="{{ $l->surname }}" required placeholder="Surname" class="border-gray-300 rounded text-sm">
                                    <input name="phone" value="{{ $l->phone }}" required placeholder="Phone" class="border-gray-300 rounded text-sm">
                                    <input name="email" type="email" value="{{ $l->email }}" required placeholder="Email" class="border-gray-300 rounded text-sm">
                                    <input name="qualification" value="{{ $l->qualification }}" required placeholder="Qualification" class="border-gray-300 rounded text-sm">
                                    <input name="university" value="{{ $l->university }}" required placeholder="University" class="border-gray-300 rounded text-sm">
                                    <input name="class_of_degree" value="{{ $l->class_of_degree }}" placeholder="Class of degree" class="border-gray-300 rounded text-sm sm:col-span-2">
                                    <div class="sm:col-span-2 flex gap-2 pt-1">
                                        <button class="bg-indigo-600 text-white px-4 py-1.5 rounded font-bold text-sm hover:bg-indigo-700">Save changes</button>
                                        <button type="button" @click="edit = false" class="text-gray-500 text-sm font-semibold">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        @empty
                            <p class="px-6 py-6 text-center text-gray-400">No resource persons yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
