{{-- Quick-access grid to the oversight college modules. Shared by the
     Proprietor and Provost dashboards; mirrors the curated sidebar. --}}
@php
    $tiles = [
        ['route' => 'students.index',    'label' => 'Students',      'desc' => 'Browse students across the college', 'icon' => '🎓'],
        ['route' => 'departments.browse','label' => 'Departments',   'desc' => 'Departments & courses of study',      'icon' => '🏛️'],
        ['route' => 'oversight.fees',    'label' => 'Fee Breakdown', 'desc' => 'Fees paid per dept · course · level', 'icon' => '💳'],
        ['route' => 'staff.index',       'label' => 'Staff',         'desc' => 'Staff directory by department',       'icon' => '👥'],
        ['route' => 'library.index',     'label' => 'Library',       'desc' => 'Catalogue of books',                  'icon' => '📚'],
        ['route' => 'inventory.index',   'label' => 'Inventory',     'desc' => 'College inventory items',             'icon' => '📦'],
    ];
@endphp
<div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
    @foreach($tiles as $t)
        @if(\Illuminate\Support\Facades\Route::has($t['route']))
            <a href="{{ route($t['route']) }}" class="bg-white p-5 rounded-2xl shadow-sm border hover:shadow-md hover:border-brand/40 transition">
                <div class="text-2xl mb-2">{{ $t['icon'] }}</div>
                <h3 class="font-bold text-gray-800 text-sm">{{ $t['label'] }}</h3>
                <p class="text-xs text-gray-500 mt-0.5">{{ $t['desc'] }}</p>
            </a>
        @endif
    @endforeach
</div>
