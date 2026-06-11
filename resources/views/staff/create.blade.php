<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Register New Staff</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @if($errors->any())
                <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">
                    <ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <form action="{{ route('staff.store') }}" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 space-y-6">
                @csrf

                <div>
                    <h3 class="font-bold text-gray-700 border-b pb-2 mb-4">Personal Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">First Name *</label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Surname *</label>
                            <input type="text" name="surname" value="{{ old('surname') }}" class="w-full border-gray-300 rounded-md shadow-sm" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Phone</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Passport Photo</label>
                            <input type="file" name="passport" accept="image/*" class="w-full text-sm">
                            <p class="text-[11px] text-gray-400 mt-1">Used for the staff ID card.</p>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="font-bold text-gray-700 border-b pb-2 mb-4">Employment</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Role *</label>
                            <select name="role" class="w-full border-gray-300 rounded-md shadow-sm" required>
                                @foreach($roles as $r)
                                    <option value="{{ $r }}" {{ old('role')===$r ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$r)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Year of Employment</label>
                            <input type="text" name="employed_year" value="{{ old('employed_year', date('Y')) }}" class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Department <span class="text-gray-300">(for HOD / lecturers)</span></label>
                            <select name="department_id" class="w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">— None —</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}" {{ old('department_id')==$d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="font-bold text-gray-700 border-b pb-2 mb-4">Next of Kin</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Name</label>
                            <input type="text" name="next_of_kin_name" value="{{ old('next_of_kin_name') }}" class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Phone</label>
                            <input type="text" name="next_of_kin_phone" value="{{ old('next_of_kin_phone') }}" class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="font-bold text-gray-700 border-b pb-2 mb-4">Assignments <span class="text-xs font-normal text-gray-400">(for teachers — select multiple)</span></h3>
                    <div class="mb-3">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Filter by Section</label>
                        <select id="sectionFilter" class="w-full sm:w-64 border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">All sections</option>
                            @foreach($sections as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Classes</label>
                            <div class="grid grid-cols-2 gap-1 max-h-44 overflow-y-auto border rounded-md p-2">
                                @forelse($classes as $c)
                                    <label class="assign-item flex items-center gap-2 text-sm py-1" data-section="{{ $c->section }}">
                                        <input type="checkbox" name="class_ids[]" value="{{ $c->id }}" class="rounded"> {{ $c->name }}
                                    </label>
                                @empty
                                    <p class="text-xs text-gray-400">No classes defined.</p>
                                @endforelse
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Subjects</label>
                            <div class="grid grid-cols-2 gap-1 max-h-44 overflow-y-auto border rounded-md p-2">
                                @forelse($subjects as $s)
                                    <label class="assign-item flex items-center gap-2 text-sm py-1" data-section="{{ $s->section }}">
                                        <input type="checkbox" name="subject_ids[]" value="{{ $s->id }}" class="rounded"> {{ $s->name }}
                                    </label>
                                @empty
                                    <p class="text-xs text-gray-400">No subjects defined.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <script>
                        document.getElementById('sectionFilter')?.addEventListener('change', function () {
                            const sec = this.value;
                            document.querySelectorAll('.assign-item').forEach(el => {
                                // Items with no section (null) stay visible (e.g. cross-section subjects).
                                el.style.display = (!sec || !el.dataset.section || el.dataset.section === sec) ? 'flex' : 'none';
                            });
                        });
                    </script>
                </div>

                <div>
                    <h3 class="font-bold text-gray-700 border-b pb-2 mb-4">Login Credentials</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Login Email <span class="text-gray-300">(auto if blank)</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="auto: f.surname@{{ setting('staff_email_domain','school.test') }}" class="w-full border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Password <span class="text-gray-300">(auto if blank)</span></label>
                            <input type="text" name="password" class="w-full border-gray-300 rounded-md shadow-sm">
                            <p class="text-[11px] text-gray-400 mt-1">Staff must change it on first login.</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center pt-4 border-t">
                    <a href="{{ route('staff.index') }}" class="text-gray-500 font-bold text-sm">← Cancel</a>
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-700">Create Staff Account</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
