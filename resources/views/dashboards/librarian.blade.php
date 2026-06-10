<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">📖 Librarian Dashboard</h2>
            <a href="{{ route('library.index') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 text-sm">Open Library</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-indigo-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Books in Catalogue</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $stats['books'] ?? 0 }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-amber-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Currently Borrowed</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $stats['borrowed'] ?? 0 }}</h3>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                    <p class="text-xs font-bold text-gray-400 uppercase">Students</p>
                    <h3 class="text-3xl font-black text-gray-800">{{ $stats['studentCount'] }}</h3>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach(['Catalogue Management', 'Issue / Return Books', 'Overdue & Fines', 'Reservations', 'E-Resources', 'Reports'] as $card)
                    <div class="bg-white p-6 rounded-xl shadow-sm">
                        <h3 class="font-bold text-gray-700">{{ $card }}</h3>
                        <p class="text-sm text-gray-400 mt-1">Manage from the Library module.</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
