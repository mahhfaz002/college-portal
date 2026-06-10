<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">🤝 Student Affairs Dashboard</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Total Students</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $stats['studentCount'] }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-indigo-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Staff</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $stats['staffCount'] }}</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach(['Welfare & Complaints', 'Clubs & Societies', 'Disciplinary Cases', 'Hostel / Accommodation', 'Student ID & Records', 'Events & Orientation'] as $card)
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <h3 class="font-bold text-gray-700">{{ $card }}</h3>
                        <p class="text-sm text-gray-400 mt-1">Coming in a later phase.</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
