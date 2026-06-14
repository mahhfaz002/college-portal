<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Welcome</p>
                <h3 class="text-2xl font-black text-gray-900 mt-1">{{ Auth::user()->name }}</h3>
                <span class="inline-block mt-2 text-xs font-bold uppercase bg-gray-100 text-gray-600 px-3 py-1 rounded-full">
                    {{ ucwords(str_replace('_', ' ', Auth::user()->role)) }}
                </span>
                @if(Auth::user()->department)
                    <span class="inline-block mt-2 ml-1 text-xs font-bold uppercase bg-gray-100 text-gray-600 px-3 py-1 rounded-full">
                        {{ Auth::user()->department }}
                    </span>
                @endif
                <p class="text-sm text-gray-500 mt-4">You are signed in. Use the menu to access the tools available to your role.</p>
                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ route('profile.edit') }}" class="bg-gray-800 text-white px-4 py-2 rounded-lg font-bold text-sm hover:bg-gray-900">Profile</a>
                    <a href="{{ route('support.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg font-bold text-sm hover:bg-gray-200">Support</a>
                </div>
            </div>

            @if($announcements->count())
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-amber-50 border-b font-bold text-amber-800">📢 Announcements</div>
                <div class="divide-y">
                    @foreach($announcements as $a)
                        <div class="px-6 py-3">
                            <p class="font-bold text-gray-800 text-sm">{{ $a->title }}</p>
                            <p class="text-sm text-gray-600 whitespace-pre-line">{{ \Illuminate\Support\Str::limit($a->body, 160) }}</p>
                            <p class="text-[10px] text-gray-400 mt-1">{{ $a->created_at->diffForHumans() }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
