<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            📦 Inventory
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b">
                            <th class="p-3">Item Name</th>
                            <th class="p-3">Category</th>
                            <th class="p-3">Quantity</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3 font-bold">{{ $item->item_name }}</td>
                            <td class="p-3">{{ $item->category }}</td>
                            <td class="p-3">{{ $item->quantity }}</td>
                            <td class="p-3">
                                <span class="px-2 py-1 rounded text-xs {{ $item->status == 'Good' ? 'bg-green-100' : 'bg-red-100' }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                            <td class="p-3">{{ $item->location }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
