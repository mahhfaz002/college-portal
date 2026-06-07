<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Staff: {{ $staff->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            @if($errors->any())
                <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">
                    <ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <form action="{{ route('staff.update', $staff) }}" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 space-y-6">
                @csrf @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">First Name *</label>
                        <input type="text" name="first_name" value="{{ old('first_name', $staff->first_name ?? explode(' ', $staff->name)[0]) }}" class="w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Surname *</label>
                        <input type="text" name="surname" value="{{ old('surname', $staff->surname) }}" class="w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Email *</label>
                        <input type="email" name="email" value="{{ old('email', $staff->email) }}" class="w-full border-gray-300 rounded-md shadow-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Role *</label>
                        <select name="role" class="w-full border-gray-300 rounded-md shadow-sm" required>
                            @foreach($roles as $r)
                                <option value="{{ $r }}" {{ old('role', $staff->role)===$r ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$r)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $staff->phone) }}" class="w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Status</label>
                        <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="active" {{ $staff->status==='active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $staff->status==='inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Department</label>
                        <input type="text" name="department" value="{{ old('department', $staff->department) }}" class="w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Year of Employment</label>
                        <input type="text" name="employed_year" value="{{ old('employed_year', $staff->employed_year) }}" class="w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Next of Kin Name</label>
                        <input type="text" name="next_of_kin_name" value="{{ old('next_of_kin_name', $staff->next_of_kin_name) }}" class="w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Next of Kin Phone</label>
                        <input type="text" name="next_of_kin_phone" value="{{ old('next_of_kin_phone', $staff->next_of_kin_phone) }}" class="w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Replace Passport Photo</label>
                        <input type="file" name="passport" accept="image/*" class="w-full text-sm">
                    </div>
                </div>

                <div class="flex justify-between items-center pt-4 border-t">
                    <a href="{{ route('staff.show', $staff) }}" class="text-gray-500 font-bold text-sm">← Cancel</a>
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-indigo-700">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
