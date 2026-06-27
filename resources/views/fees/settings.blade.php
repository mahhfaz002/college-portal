<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Fee Settings</h2></x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Configurable Fees</div>
                <form method="POST" action="{{ route('fees.settings.update') }}" class="p-6 space-y-4">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Change of Course Fee (₦)</label>
                        <input type="number" name="change_of_course_fee" value="{{ old('change_of_course_fee', $changeOfCourseFee) }}"
                               min="0" step="0.01" required
                               class="w-full max-w-xs rounded-lg border-gray-300">
                        <p class="text-xs text-gray-500 mt-1">This amount is charged when a student applies for a change of course.</p>
                    </div>
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-indigo-700">Save Fee Settings</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
