@php
    $role = Auth::user()->role ?? 'guest';
    // Applicants (pre-registration) and students are not staff — they get a
    // minimal menu, not the staff modules.
    $isStaff = !in_array($role, ['student', 'applicant']);
    // Roles with system-wide oversight / settings access.
    $isAdminish = in_array($role, ['proprietor', 'registrar', 'mis']);
    // Roles that see the academic structure (departments / programs / courses).
    $academic = ['registrar', 'proprietor', 'mis', 'academic_secretary', 'exam_officer', 'lecturer', 'hod', 'assistant_hod'];
    $brandName = current_college()->name ?? ($school['name'] ?? 'MAHHFAZ College of Health Sciences and Technology, Jalingo');
    // Student registration state drives the "Registration" prompt until the HOD approves.
    $studentReg = $role === 'student'
        ? \App\Models\Student::where('email', Auth::user()->email)->value('registration_status')
        : null;
@endphp
<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center gap-2">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        @if(current_college()?->logo_path)
                            <img src="{{ media_url(current_college()->logo_path) }}" alt="Logo" class="h-9 w-9 rounded object-contain">
                        @elseif(!empty($school['logo']))
                            <img src="{{ media_url($school['logo']) }}" alt="Logo" class="h-9 w-9 rounded object-contain">
                        @else
                            <x-application-logo class="block h-9 w-auto fill-current" style="color: var(--brand)" />
                        @endif
                        <span class="font-bold text-gray-800 hidden md:inline truncate max-w-[18rem]">{{ $brandName }}</span>
                    </a>
                </div>

                <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @if($role === 'applicant')
                        <x-nav-link :href="route('dashboard')">{{ __('Admission') }}</x-nav-link>
                        <x-nav-link :href="route('dashboard').'#fees'">{{ __('Fees') }}</x-nav-link>
                    @endif

                    @if($role === 'student')
                        @if(($studentReg ?? null) && $studentReg !== 'registered')
                            <x-nav-link :href="route('registration.documents')" :active="request()->routeIs('registration.*')">{{ __('Registration') }}</x-nav-link>
                        @endif
                        <x-nav-link :href="route('myexams.available')" :active="request()->routeIs('myexams.*')">{{ __('My Exams') }}</x-nav-link>
                        <x-nav-link :href="route('timetable.index')" :active="request()->routeIs('timetable.*')">{{ __('Timetable') }}</x-nav-link>
                        <x-nav-link :href="route('dashboard').'#results'">{{ __('Results') }}</x-nav-link>
                        <x-nav-link :href="route('dashboard').'#fees'">{{ __('Fees') }}</x-nav-link>
                        <x-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">{{ __('Notifications') }}</x-nav-link>
                    @endif

                    {{-- Capability-driven menu: each link shows only for roles that can use it. --}}
                    @if($isStaff)
                        @can('view_students')
                            <x-nav-link :href="route('students.index')" :active="request()->routeIs('students.*')">{{ __('Students') }}</x-nav-link>
                        @endcan

                        @can('manage_departments')
                            <x-nav-link :href="route('structure.index')" :active="request()->routeIs('structure.*')">{{ __('Academic Structure') }}</x-nav-link>
                        @elsecan('view_departments')
                            <x-nav-link :href="route('departments.index')" :active="request()->routeIs('departments.*')">{{ __('Departments') }}</x-nav-link>
                        @endcan

                        @can('manage_subjects')
                            <x-nav-link :href="route('courses.builder')" :active="request()->routeIs('courses.builder')">{{ __('Create Courses') }}</x-nav-link>
                        @endcan
                        @can('view_subjects')
                            <x-nav-link :href="route('subjects.index')" :active="request()->routeIs('subjects.*')">{{ __('Courses') }}</x-nav-link>
                        @endcan
                        @can('assign_courses')
                            <x-nav-link :href="route('subjects.index')" :active="false">{{ __('Assign Courses') }}</x-nav-link>
                        @endcan

                        @can('view_attendance')
                            <x-nav-link :href="route('attendance.index')" :active="request()->routeIs('attendance.*')">{{ __('Attendance') }}</x-nav-link>
                        @endcan
                        @can('author_questions')
                            <x-nav-link :href="route('exams.my')" :active="request()->routeIs('exams.my')">{{ __('Set Exams') }}</x-nav-link>
                        @endcan

                        @can('view_staff')
                            <x-nav-link :href="route('staff.index')" :active="request()->routeIs('staff.*')">{{ __('Staff') }}</x-nav-link>
                        @endcan

                        @can('view_applications')
                            <x-nav-link :href="route('admission.admin')" :active="request()->routeIs('admission.admin')">{{ __('Applications') }}</x-nav-link>
                        @endcan
                        @can('manage_admissions')
                            <x-nav-link :href="route('admissions.review')" :active="request()->routeIs('admissions.review')">{{ __('Admission Queue') }}</x-nav-link>
                        @endcan

                        @can('approve_registration')
                            <x-nav-link :href="route('hod.students')" :active="request()->routeIs('hod.students')">{{ __('Dept Students') }}</x-nav-link>
                            <x-nav-link :href="route('hod.resource-persons')" :active="request()->routeIs('hod.resource-persons')">{{ __('Resource Persons') }}</x-nav-link>
                            <x-nav-link :href="route('hod.grading')" :active="request()->routeIs('hod.grading')">{{ __('Grading') }}</x-nav-link>
                            <x-nav-link :href="route('hod.registrations')" :active="request()->routeIs('hod.registrations')">{{ __('Registrations') }}</x-nav-link>
                        @endcan

                        @can('manage_fees')
                            <x-nav-link :href="route('fees.index')" :active="request()->routeIs('fees.index')">{{ __('Fees') }}</x-nav-link>
                            <x-nav-link :href="route('fees.orders.index')" :active="request()->routeIs('fees.orders.*')">{{ __('Payment Orders') }}</x-nav-link>
                        @endcan
                        @cannot('manage_fees')
                            @can('view_fees')
                                <x-nav-link :href="route('fees.index')" :active="request()->routeIs('fees.index')">{{ __('Fees') }}</x-nav-link>
                            @endcan
                        @endcannot

                        @can('manage_library')
                            <x-nav-link :href="route('library.index')" :active="request()->routeIs('library.*')">{{ __('Library') }}</x-nav-link>
                        @endcan

                        @can('view_staff_attendance')
                            <x-nav-link :href="route('staff.attendance')" :active="request()->routeIs('staff.attendance')">{{ __('Staff Activity') }}</x-nav-link>
                        @endcan

                        @can('manage_announcements')
                            <x-nav-link :href="route('announcements.index')" :active="request()->routeIs('announcements.*')">{{ __('Announcements') }}</x-nav-link>
                        @endcan
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-2">
                @php $notif = \App\Support\Notifications::forUser(Auth::user()); @endphp
                @if(($notif['count'] ?? 0) > 0)
                    <a href="{{ route('notifications.index') }}" title="{{ $notif['count'] }} {{ $notif['label'] }}"
                       class="relative inline-flex items-center p-2 text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none text-white bg-red-600 rounded-full">{{ $notif['count'] }}</span>
                    </a>
                @endif
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
                        @if(in_array($role, ['exam_officer','mis','proprietor','hod']))
                            <x-dropdown-link :href="route('exams.index')">{{ __('Exams') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('exams.queries')">{{ __('Result Queries') }}</x-dropdown-link>
                        @endif
                        @if(in_array($role, ['bursar','mis']) && \Illuminate\Support\Facades\Route::has('payroll.index'))
                            <x-dropdown-link :href="route('payroll.index')">{{ __('HR / Payroll') }}</x-dropdown-link>
                        @endif
                        @if(in_array($role, ['proprietor','mis','bursar']))
                            <x-dropdown-link :href="route('transport.index')">{{ __('Transport') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('alumni.index')">{{ __('Alumni') }}</x-dropdown-link>
                        @endif
                    </x-slot>
                </x-dropdown>
                @endif

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <span class="ml-2 text-[10px] uppercase font-bold bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">{{ str_replace('_', ' ', $role) }}</span>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                        <x-dropdown-link :href="route('support.index')">{{ __('Support') }}</x-dropdown-link>

                        @if($isAdminish && \Illuminate\Support\Facades\Route::has('settings.index'))
                            <x-dropdown-link :href="route('settings.index')">{{ __('College Settings') }}</x-dropdown-link>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
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
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">{{ __('Dashboard') }}</x-responsive-nav-link>

            {{-- Capability-driven (mirrors the desktop menu) --}}
            @can('view_students')
                <x-responsive-nav-link :href="route('students.index')" :active="request()->routeIs('students.*')">{{ __('Students') }}</x-responsive-nav-link>
            @endcan
            @can('manage_departments')
                <x-responsive-nav-link :href="route('departments.index')">{{ __('Departments') }}</x-responsive-nav-link>
            @endcan
            @can('manage_programs')
                <x-responsive-nav-link :href="route('programs.index')">{{ __('Programs') }}</x-responsive-nav-link>
            @endcan
            @can('view_subjects')
                <x-responsive-nav-link :href="route('subjects.index')" :active="request()->routeIs('subjects.*')">{{ __('Courses') }}</x-responsive-nav-link>
            @endcan
            @can('view_attendance')
                <x-responsive-nav-link :href="route('attendance.index')" :active="request()->routeIs('attendance.*')">{{ __('Attendance') }}</x-responsive-nav-link>
            @endcan
            @can('author_questions')
                <x-responsive-nav-link :href="route('exams.my')" :active="request()->routeIs('exams.my')">{{ __('Set Exams') }}</x-responsive-nav-link>
            @endcan
            @can('view_staff')
                <x-responsive-nav-link :href="route('staff.index')" :active="request()->routeIs('staff.*')">{{ __('Staff') }}</x-responsive-nav-link>
            @endcan
            @can('view_applications')
                <x-responsive-nav-link :href="route('admission.admin')" :active="request()->routeIs('admission.admin')">{{ __('Applications') }}</x-responsive-nav-link>
            @endcan
            @can('manage_admissions')
                <x-responsive-nav-link :href="route('admissions.review')" :active="request()->routeIs('admissions.review')">{{ __('Admission Queue') }}</x-responsive-nav-link>
            @endcan
            @can('manage_inventory')
                <x-responsive-nav-link :href="route('inventory.index')" :active="request()->routeIs('inventory.*')">{{ __('Inventory') }}</x-responsive-nav-link>
            @endcan
            @can('manage_fees')
                <x-responsive-nav-link :href="route('fees.index')" :active="request()->routeIs('fees.index')">{{ __('Fees') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('fees.orders.index')" :active="request()->routeIs('fees.orders.*')">{{ __('Payment Orders') }}</x-responsive-nav-link>
            @endcan
            @can('approve_registration')
                <x-responsive-nav-link :href="route('hod.students')">{{ __('Dept Students') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('hod.resource-persons')">{{ __('Resource Persons') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('hod.registrations')">{{ __('Registrations') }}</x-responsive-nav-link>
            @endcan
            @can('manage_library')
                <x-responsive-nav-link :href="route('library.index')">{{ __('Library') }}</x-responsive-nav-link>
            @endcan
            @can('manage_announcements')
                <x-responsive-nav-link :href="route('announcements.index')" :active="request()->routeIs('announcements.*')">{{ __('Announcements') }}</x-responsive-nav-link>
            @endcan
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">{{ __('Profile') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('support.index')">{{ __('Support') }}</x-responsive-nav-link>
                @if($isAdminish && \Illuminate\Support\Facades\Route::has('settings.index'))
                    <x-responsive-nav-link :href="route('settings.index')">{{ __('College Settings') }}</x-responsive-nav-link>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
