<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Student List - Nigerian Secondary School Portal') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-500 text-white rounded shadow-md">
                    {{ session('success') }}
                </div>
            @endif

           <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    <h3 class="text-lg font-bold text-gray-700">All Registered Students</h3>

    <form action="{{ route('students.index') }}" method="GET" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search name or ID..."
               class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        <button type="submit" style="background-color: #4b5563; color: white; padding: 8px 15px; border-radius: 5px; font-weight: bold; font-size: 13px;">
            Search
        </button>
        @if(request('search'))
            <a href="{{ route('students.index') }}" class="text-sm text-red-600 flex items-center underline">Clear</a>
        @endif
    </form>

    @can('manage_students')
    <a href="{{ route('students.create') }}"
       style="background-color: #2563eb; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block;">
        + Admit New Student
    </a>
    @endcan
</div>

                <table class="min-w-full border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-2">S/N</th>
                            <th class="border p-2">Full Name</th>
                            <th class="border p-2">Admission No</th>
                            <th class="border p-2">Class</th>
                            <th class="border p-2">Fees Balance</th>
                            <th class="border p-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr class="hover:bg-gray-50">
                                <td class="border p-2 text-center">{{ $loop->iteration }}</td>

                                <td class="border p-2 font-semibold">
                                    <a href="{{ route('students.show', $student->id) }}" class="text-blue-600 hover:underline" title="View Statement of Account">
                                        {{ $student->full_name }}
                                    </a>
                                </td>

                                <td class="border p-2">{{ $student->admission_number }}</td>
                                <td class="border p-2">{{ $student->class_arm }}</td>
                                <td class="border p-2 text-red-600 font-bold">₦{{ number_format($student->fees_balance, 2) }}</td>
                                <td class="border p-2">
                                    <div style="display: flex; justify-content: center; align-items: center; gap: 10px;">

                                        <a href="{{ route('students.show', $student->id) }}"
                                           style="background-color: #6b7280; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; white-space: nowrap;">
                                            View
                                        </a>

                                        @can('manage_fees')
                                        <a href="{{ route('payments.create', $student->id) }}"
                                           style="background-color: #059669; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; white-space: nowrap;">
                                            Pay Fees
                                        </a>
                                        @endcan

                                        @can('manage_students')
                                        <a href="{{ route('students.edit', $student->id) }}"
                                           style="background-color: #4b5563; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; white-space: nowrap;">
                                            Edit
                                        </a>
                                        @endcan

                                        @can('enter_scores')
                                        <a href="{{ route('scores.create', ['class' => $student->class_arm]) }}"
                                           style="background-color: #2563eb; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 11px; font-weight: bold; white-space: nowrap;">
                                            Scores
                                        </a>
                                        @endcan

                                        @can('manage_students')
                                        <form action="{{ route('students.destroy', $student->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this student?');" style="margin: 0;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    style="background-color: #dc2626; color: white; padding: 6px 12px; border-radius: 4px; border: none; font-size: 12px; font-weight: bold; cursor: pointer; white-space: nowrap;">
                                                Delete
                                            </button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="border p-8 text-center text-gray-500 italic">
                                    No students found in the database. Click "+ Admit New Student" to begin.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</x-app-layout>
