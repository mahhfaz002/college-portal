@php
    $role = Auth::user()->role ?? 'guest';
    $isStaff = $role !== 'student';
    $isAdminish = in_array($role, ['proprietor', 'principal', 'admin', 'ict']);
@endphp
<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center gap-2">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        @if(!empty($school['logo']))
                            <img src="{{ media_url($school['logo']) }}" alt="Logo" class="h-9 w-9 rounded object-contain">
                        @else
                            <x-application-logo class="block h-9 w-auto fill-current" style="color: var(--brand)" />
                        @endif
                        <span class="font-bold text-gray-800 hidden md:inline">{{ $school['name'] ?? 'School Portal' }}</span>
                    </a>
                </div>

                <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @if($role === 'student')
                        <x-nav-link :href="route('myexams.available')" :active="request()->routeIs('myexams.*')">
                            {{ __('My Exams') }}
                        </x-nav-link>
                    @endif

                    @if($isStaff)
                        <x-nav-link :href="route('students.index')" :active="request()->routeIs('students.*')">
                            {{ __('Students') }}
                        </x-nav-link>

                        <x-nav-link :href="route('subjects.index')" :active="request()->routeIs('subjects.*')">
                            {{ __('Subjects') }}
                        </x-nav-link>

                        <x-nav-link :href="route('attendance.index')" :active="request()->routeIs('attendance.*')">
                            {{ __('Attendance') }}
                        </x-nav-link>

                        @if(\Illuminate\Support\Facades\Route::has('announcements.index'))
                            <x-nav-link :href="route('announcements.index')" :active="request()->routeIs('announcements.*')">
                                {{ __('Announcements') }}
                            </x-nav-link>
                        @endif
                    @endif

                    @if(in_array($role, ['principal', 'proprietor']))
                        <x-nav-link :href="route('staff.index')" :active="request()->routeIs('staff.*')">
                            {{ __('Staff') }}
                        </x-nav-link>
                    @endif

                    @if(in_array($role, ['admin', 'ict', 'proprietor']))
                        <x-nav-link :href="route('inventory.index')" :active="request()->routeIs('inventory.*')">
                            {{ __('Inventory') }}
                        </x-nav-link>
                        <x-nav-link :href="route('admission.admin')" :active="request()->routeIs('admission.admin')">
                            {{ __('Admissions') }}
                        </x-nav-link>
                    @endif

                    @if(in_array($role, ['accountant', 'proprietor']))
                        <x-nav-link :href="route('fees.index')" :active="request()->routeIs('fees.*')">
                            {{ __('Fees') }}
                        </x-nav-link>
                    @endif

                    @if(in_array($role, ['principal', 'proprietor']))
                        <x-nav-link :href="route('staff.attendance')" :active="request()->routeIs('staff.attendance')">
                            {{ __('Teacher Activity') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-2">
                @if($isStaff)
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-500 hover:text-gray-700 focus:outline-none transition">
                            {{ __('More') }}
                            <svg class="ms-1 fill-current h-4 w-4" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('timetable.index')">{{ __('Timetable') }}</x-dropdown-link>
                        <x-dropdown-link :href="route('library.index')">{{ __('Library') }}</x-dropdown-link>
                        @if(in_array($role, ['exam_officer','ict','principal','proprietor']))
                            <x-dropdown-link :href="route('exams.index')">{{ __('Exams') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('exams.queries')">{{ __('Result Queries') }}</x-dropdown-link>
                        @endif
                        @if(in_array($role, ['proprietor','principal','admin','ict','accountant']))
                            <x-dropdown-link :href="route('transport.index')">{{ __('Transport') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('hr.index')">{{ __('HR / Payroll') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('alumni.index')">{{ __('Alumni') }}</x-dropdown-link>
                        @endif
                    </x-slot>
                </x-dropdown>
                @endif

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <span class="ml-2 text-[10px] uppercase font-bold bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">{{ $role }}</span>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <x-dropdown-link :href="route('support.index')">
                            {{ __('Support') }}
                        </x-dropdown-link>

                        @if($isAdminish && \Illuminate\Support\Facades\Route::has('settings.index'))
                            <x-dropdown-link :href="route('settings.index')">
                                {{ __('School Settings') }}
                            </x-dropdown-link>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @if($isStaff)
                <x-responsive-nav-link :href="route('students.index')" :active="request()->routeIs('students.*')">
                    {{ __('Students') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('subjects.index')" :active="request()->routeIs('subjects.*')">
                    {{ __('Subjects') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('attendance.index')" :active="request()->routeIs('attendance.*')">
                    {{ __('Attendance') }}
                </x-responsive-nav-link>
                @if(\Illuminate\Support\Facades\Route::has('announcements.index'))
                    <x-responsive-nav-link :href="route('announcements.index')" :active="request()->routeIs('announcements.*')">
                        {{ __('Announcements') }}
                    </x-responsive-nav-link>
                @endif
            @endif

            @if(in_array($role, ['principal', 'proprietor']))
                <x-responsive-nav-link :href="route('staff.index')" :active="request()->routeIs('staff.*')">
                    {{ __('Staff') }}
                </x-responsive-nav-link>
            @endif

            @if(in_array($role, ['admin', 'ict', 'proprietor']))
                <x-responsive-nav-link :href="route('inventory.index')" :active="request()->routeIs('inventory.*')">
                    {{ __('Inventory') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admission.admin')" :active="request()->routeIs('admission.admin')">
                    {{ __('Admissions') }}
                </x-responsive-nav-link>
            @endif

            @if($isStaff)
                <x-responsive-nav-link :href="route('timetable.index')">{{ __('Timetable') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('library.index')">{{ __('Library') }}</x-responsive-nav-link>
                @if(in_array($role, ['exam_officer','ict','principal','proprietor']))
                    <x-responsive-nav-link :href="route('exams.index')">{{ __('Exams') }}</x-responsive-nav-link>
                @endif
                @if(in_array($role, ['proprietor','principal','admin','ict','accountant']))
                    <x-responsive-nav-link :href="route('transport.index')">{{ __('Transport') }}</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('hr.index')">{{ __('HR / Payroll') }}</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('alumni.index')">{{ __('Alumni') }}</x-responsive-nav-link>
                @endif
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('support.index')">
                    {{ __('Support') }}
                </x-responsive-nav-link>

                @if($isAdminish && \Illuminate\Support\Facades\Route::has('settings.index'))
                    <x-responsive-nav-link :href="route('settings.index')">
                        {{ __('School Settings') }}
                    </x-responsive-nav-link>
                @endif

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
