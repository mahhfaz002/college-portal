<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            🛠️ System Switchboard: Preview All Dashboards
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-gray-900 p-8 rounded-2xl shadow-2xl border-b-8 border-indigo-600">
                <p class="text-indigo-400 font-bold mb-8 uppercase tracking-widest text-sm">Main System Hub</p>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                    <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 hover:border-indigo-500 transition group">
                        <div class="text-3xl mb-3">💰</div>
                        <h3 class="text-white font-bold text-lg">Proprietor</h3>
                        <p class="text-gray-400 text-xs mb-4">Financials, Stats, Global Search.</p>
                        <a href="{{ route('dashboard') }}" class="text-indigo-400 font-bold text-sm group-hover:underline italic">Launch Dashboard →</a>
                    </div>

                    <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 hover:border-green-500 transition group">
                        <div class="text-3xl mb-3">👩‍🏫</div>
                        <h3 class="text-white font-bold text-lg">Lecturer</h3>
                        <p class="text-gray-400 text-xs mb-4">Course register, Attendance, Results.</p>
                        <a href="/dashboard?role_preview=lecturer" class="text-green-400 font-bold text-sm group-hover:underline italic">Launch Dashboard →</a>
                    </div>

                    <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 hover:border-yellow-500 transition group">
                        <div class="text-3xl mb-3">📑</div>
                        <h3 class="text-white font-bold text-lg">Bursar</h3>
                        <p class="text-gray-400 text-xs mb-4">Fees, Payments, Debt recovery.</p>
                        <a href="/dashboard?role_preview=bursar" class="text-yellow-400 font-bold text-sm group-hover:underline italic">Launch Dashboard →</a>
                    </div>

                    <div class="bg-gray-800 p-6 rounded-xl border border-gray-700 hover:border-blue-500 transition group">
                        <div class="text-3xl mb-3">⚙️</div>
                        <h3 class="text-white font-bold text-lg">Staff Admin</h3>
                        <p class="text-gray-400 text-xs mb-4">Manage users and course assignments.</p>
                        <a href="{{ route('staff.index') }}" class="text-blue-400 font-bold text-sm group-hover:underline italic">Launch Portal →</a>
                    </div>

                </div>

                <div class="mt-12 bg-gray-800 p-4 rounded-lg flex items-center justify-between">
                    <span class="text-gray-500 text-sm">System Status: <span class="text-green-500">Online</span></span>
                    <span class="text-gray-500 text-sm">Laravel v11.x</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
