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
                    <h3 class="font-bold text-gray-700 border-b pb-2 mb-4">Login Credentials</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Login Email <span class="text-gray-300">(auto if blank)</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="auto: f.surname@{{ optional(current_college())->domain ?: setting('staff_email_domain','school.test') }}" class="w-full border-gray-300 rounded-md shadow-sm">
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
