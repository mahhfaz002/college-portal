<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">📚 Library</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif

            {{-- Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach([['Titles',$stats['titles'],'indigo'],['Total Copies',$stats['copies'],'emerald'],['On Loan',$stats['on_loan'],'amber'],['Overdue',$stats['overdue'],'red']] as [$l,$v,$c])
                    <div class="bg-white rounded-xl border p-4 border-t-4 border-{{ $c }}-500">
                        <p class="text-xs uppercase text-gray-400 font-bold">{{ $l }}</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $v }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid lg:grid-cols-3 gap-8">
                {{-- Add book — librarian only --}}
                @can('manage_library')
                <div class="bg-white rounded-2xl shadow-sm border p-6">
                    <h3 class="font-bold text-gray-800 mb-4">Add a Book</h3>
                    <form method="POST" action="{{ route('library.books.store') }}" class="space-y-3">
                        @csrf
                        <input name="title" required placeholder="Title *" class="w-full border-gray-300 rounded-lg">
                        <input name="author" placeholder="Author" class="w-full border-gray-300 rounded-lg">
                        <input name="isbn" placeholder="ISBN" class="w-full border-gray-300 rounded-lg">
                        <input name="category" placeholder="Category (e.g. Anatomy)" class="w-full border-gray-300 rounded-lg">
                        <input name="total_copies" type="number" min="1" value="1" required placeholder="Copies *" class="w-full border-gray-300 rounded-lg">
                        <button class="w-full bg-emerald-600 text-white py-2 rounded-lg font-bold hover:bg-emerald-700">Add Book</button>
                    </form>
                </div>
                @endcan

                {{-- Catalogue --}}
                <div class="@can('manage_library') lg:col-span-2 @else lg:col-span-3 @endcan bg-white rounded-2xl shadow-sm border overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Catalogue</div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                                <tr><th class="px-4 py-2 text-left">Title</th><th class="px-4 py-2 text-left">Author</th><th class="px-4 py-2 text-left">Avail.</th>@can('manage_library')<th class="px-4 py-2 text-right">Issue</th>@endcan</tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse($books as $book)
                                    <tr>
                                        <td class="px-4 py-2 font-semibold text-gray-800">{{ $book->title }}<br><span class="text-xs text-gray-400">{{ $book->category }}</span></td>
                                        <td class="px-4 py-2 text-gray-500">{{ $book->author }}</td>
                                        <td class="px-4 py-2">{{ $book->available_copies }}/{{ $book->total_copies }}</td>
                                        @can('manage_library')
                                        <td class="px-4 py-2 text-right">
                                            <form method="POST" action="{{ route('library.issue') }}" class="flex gap-1 justify-end items-center">
                                                @csrf
                                                <input type="hidden" name="book_id" value="{{ $book->id }}">
                                                <select name="student_id" required class="border-gray-300 rounded text-xs py-1 max-w-[10rem]">
                                                    <option value="">Student…</option>
                                                    @foreach($students as $s)<option value="{{ $s->id }}">{{ $s->full_name }}</option>@endforeach
                                                </select>
                                                <button @disabled($book->available_copies < 1) class="bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold disabled:opacity-40">Issue</button>
                                            </form>
                                        </td>
                                        @endcan
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No books in the catalogue.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Active loans --}}
            <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Books on Loan</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr><th class="px-4 py-2 text-left">Book</th><th class="px-4 py-2 text-left">Borrower</th><th class="px-4 py-2 text-left">Due</th><th class="px-4 py-2 text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($records as $r)
                            <tr>
                                <td class="px-4 py-2 font-semibold text-gray-800">{{ $r->book->title ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $r->student->full_name ?? '—' }}</td>
                                <td class="px-4 py-2 {{ $r->due_at && $r->due_at->isPast() ? 'text-red-600 font-bold':'text-gray-500' }}">{{ optional($r->due_at)->format('d M Y') }}</td>
                                <td class="px-4 py-2 text-right">
                                    @can('manage_library')
                                    <form method="POST" action="{{ route('library.return', $r) }}">@csrf
                                        <button class="bg-gray-800 text-white px-3 py-1 rounded text-xs font-bold">Return</button>
                                    </form>
                                    @else
                                    <span class="text-gray-300">—</span>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No books currently on loan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
