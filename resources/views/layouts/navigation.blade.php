@php
    $role = Auth::user()->role ?? 'guest';
    // Applicants/students get a minimal menu; the platform super-admin has its
    // own cross-college menu (no per-college staff modules).
    $isStaff = !in_array($role, ['student', 'applicant', 'superadmin']);
    // College settings access — MIS only. (Proprietor is view-only oversight.)
    $isAdminish = in_array($role, ['mis']);
    $brandName = current_college()->name ?? ($school['name'] ?? 'College Portal');
    $brandAcr  = current_college()?->acronym ?? \Illuminate\Support\Str::substr($brandName, 0, 2);
    // Student registration state drives the "Registration" prompt until the HOD approves.
    $studentReg = $role === 'student'
        ? \App\Models\Student::where('email', Auth::user()->email)->value('registration_status')
        : null;
    $notif = \App\Support\Notifications::forUser(Auth::user());
@endphp

<div x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">

    {{-- ===================== Sticky top bar ===================== --}}
    {{-- Stays visible on scroll so the hamburger is always reachable. --}}
    <header class="sticky top-0 z-40 bg-white border-b border-gray-100 shadow-sm">
        <div class="h-16 px-3 sm:px-5 flex items-center gap-3">
            {{-- Hamburger (three lines) — toggles the sidebar on desktop AND mobile --}}
            <button @click="sidebarOpen = !sidebarOpen" aria-label="Toggle menu"
                    class="p-2 -ml-1 rounded-lg text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 min-w-0">
                @if(current_college()?->logo_path)
                    <img src="{{ media_url(current_college()->logo_path) }}" alt="Logo" class="h-9 w-9 rounded-lg object-contain bg-white shadow-sm shrink-0">
                @elseif(!empty($school['logo']))
                    <img src="{{ media_url($school['logo']) }}" alt="Logo" class="h-9 w-9 rounded-lg object-contain bg-white shadow-sm shrink-0">
                @else
                    <span class="h-9 w-9 rounded-lg grid place-items-center text-white font-bold bg-brand shadow-sm shrink-0">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($brandAcr,0,2)) }}</span>
                @endif
                <span class="font-bold text-slate-900 hidden sm:inline truncate max-w-[10rem] md:max-w-[16rem]">{{ $brandName }}</span>
            </a>

            {{-- ===== Right cluster: notification bell + profile ===== --}}
            <div class="ml-auto flex items-center gap-1 sm:gap-2">
                <a href="{{ route('notifications.index') }}" title="{{ ($notif['count'] ?? 0) }} {{ $notif['label'] ?? 'notifications' }}"
                   class="relative inline-flex items-center p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    @if(($notif['count'] ?? 0) > 0)
                        <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none text-white bg-red-600 rounded-full">{{ $notif['count'] }}</span>
                    @endif
                </a>

                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 p-1 sm:pr-2 rounded-full hover:bg-gray-100 focus:outline-none">
                            <span class="h-9 w-9 rounded-full grid place-items-center text-white font-bold bg-brand shrink-0">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(Auth::user()->name, 0, 1)) }}</span>
                            <svg class="hidden sm:block fill-current h-4 w-4 text-gray-400" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        {{-- The signed-in account --}}
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-bold text-gray-800 truncate">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                            <span class="mt-1 inline-block text-[10px] uppercase font-bold bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">{{ str_replace('_', ' ', $role) }}</span>
                        </div>
                        <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                        <x-dropdown-link :href="route('support.index')">{{ __('Support') }}</x-dropdown-link>
                        @if($isAdminish && \Illuminate\Support\Facades\Route::has('settings.index'))
                            <x-dropdown-link :href="route('settings.index')">{{ __('College Settings') }}</x-dropdown-link>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">{{ __('Log Out') }}</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </header>

    {{-- ===================== Backdrop ===================== --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" x-transition.opacity
         class="fixed inset-0 z-40 bg-gray-900/40"></div>

    {{-- ===================== Off-canvas left sidebar ===================== --}}
    {{-- Single drawer used on BOTH desktop and mobile; slides in/out via the hamburger. --}}
    <aside x-cloak
           class="fixed top-0 left-0 z-50 h-full w-72 max-w-[82%] bg-white border-r border-gray-100 shadow-2xl flex flex-col transform transition-transform duration-200 ease-in-out"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

        <div class="h-16 px-4 flex items-center justify-between border-b border-gray-100 shrink-0">
            <a href="{{ route('dashboard') }}" class="font-bold text-slate-900 truncate">{{ $brandName }}</a>
            <button @click="sidebarOpen = false" aria-label="Close menu" class="p-2 rounded-lg text-gray-400 hover:bg-gray-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto py-3 px-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">{{ __('Dashboard') }}</x-responsive-nav-link>

            @if($role === 'superadmin')
                <x-responsive-nav-link :href="route('platform.dashboard')" :active="request()->routeIs('platform.dashboard')">{{ __('Overview') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('platform.colleges')" :active="request()->routeIs('platform.colleges')">{{ __('Colleges') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('platform.register')" :active="request()->routeIs('platform.register')">{{ __('Register College') }}</x-responsive-nav-link>
            @endif

            @if($role === 'applicant')
                <x-responsive-nav-link :href="route('dashboard')">{{ __('Admission') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('dashboard').'#fees'">{{ __('Fees') }}</x-responsive-nav-link>
            @endif

            @if($role === 'student')
                @if(($studentReg ?? null) && $studentReg !== 'registered')
                    <x-responsive-nav-link :href="route('registration.documents')" :active="request()->routeIs('registration.*')">{{ __('Registration') }}</x-responsive-nav-link>
                @endif
                <x-responsive-nav-link :href="route('timetable.index')" :active="request()->routeIs('timetable.*')">{{ __('Timetable') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('library.index')" :active="request()->routeIs('library.*')">{{ __('Library') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('dashboard').'#results'">{{ __('Results') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('dashboard').'#fees'">{{ __('Fees') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('change-of-course.index')" :active="request()->routeIs('change-of-course.*')">{{ __('Change of Course') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">{{ __('Notifications') }}</x-responsive-nav-link>
            @endif

            {{-- Capability-driven staff menu (each link shows only for roles that can use it) --}}
            @if($isStaff)
                @can('view_students')
                    <x-responsive-nav-link :href="route('students.index')" :active="request()->routeIs('students.*')">{{ __('Students') }}</x-responsive-nav-link>
                @endcan

                @can('manage_departments')
                    <x-responsive-nav-link :href="route('structure.index')" :active="request()->routeIs('structure.*')">{{ __('Academic Structure') }}</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('programs.index')" :active="request()->routeIs('programs.*')">{{ __('Programs') }}</x-responsive-nav-link>
                @elsecan('assign_courses')
                    <x-responsive-nav-link :href="route('academic.departments')" :active="request()->routeIs('academic.departments')">{{ __('Departments') }}</x-responsive-nav-link>
                @elsecan('view_departments')
                    @if(in_array($role, ['proprietor', 'provost']))
                        <x-responsive-nav-link :href="route('departments.browse')" :active="request()->routeIs('departments.browse')">{{ __('Departments') }}</x-responsive-nav-link>
                    @else
                        <x-responsive-nav-link :href="route('departments.index')" :active="request()->routeIs('departments.*')">{{ __('Departments') }}</x-responsive-nav-link>
                    @endif
                @endcan

                @can('manage_subjects')
                    <x-responsive-nav-link :href="route('courses.builder')" :active="request()->routeIs('courses.builder')">{{ __('Create Courses') }}</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('academic.courses')" :active="request()->routeIs('academic.courses')">{{ __('Courses') }}</x-responsive-nav-link>
                @elsecan('view_subjects')
                    <x-responsive-nav-link :href="route('subjects.index')" :active="request()->routeIs('subjects.*')">{{ __('Courses') }}</x-responsive-nav-link>
                @endcan
                @can('assign_courses')
                    <x-responsive-nav-link :href="route('academic.assign')" :active="request()->routeIs('academic.assign')">{{ __('Assign Courses') }}</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('change-of-course.review')" :active="request()->routeIs('change-of-course.review')">{{ __('Change of Course') }}</x-responsive-nav-link>
                @endcan

                @can('author_questions')
                    <x-responsive-nav-link :href="route('exams.my')" :active="request()->routeIs('exams.my')">{{ __('Set Exam Questions') }}</x-responsive-nav-link>
                @endcan

                @can('assign_courses')
                    <x-responsive-nav-link :href="route('academic.staff')" :active="request()->routeIs('academic.staff')">{{ __('Staff') }}</x-responsive-nav-link>
                @elsecan('view_staff')
                    <x-responsive-nav-link :href="route('staff.index')" :active="request()->routeIs('staff.*')">{{ __('Staff') }}</x-responsive-nav-link>
                @endcan

                @can('view_applications')
                    <x-responsive-nav-link :href="route('admission.admin')" :active="request()->routeIs('admission.admin')">{{ __('Applications') }}</x-responsive-nav-link>
                @endcan
                @can('manage_admissions')
                    <x-responsive-nav-link :href="route('admissions.review')" :active="request()->routeIs('admissions.review')">{{ __('Admission Queue') }}</x-responsive-nav-link>
                @endcan
                @if($role === 'registrar')
                    <x-responsive-nav-link :href="route('change-of-course.approvals')" :active="request()->routeIs('change-of-course.approvals')">{{ __('Change of Course') }}</x-responsive-nav-link>
                @endif

                {{-- HOD / Assistant HOD --}}
                @can('approve_registration')
                    <x-responsive-nav-link :href="route('hod.courses')" :active="request()->routeIs('hod.courses')">{{ __('Courses') }}</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('hod.exam-reviews')" :active="request()->routeIs('hod.exam-reviews')">{{ __('Exam Reviews') }}</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('hod.students')" :active="request()->routeIs('hod.students*')">{{ __('Students') }}</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('hod.resource-persons')" :active="request()->routeIs('hod.resource-persons')">{{ __('Resource Persons') }}</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('hod.grading')" :active="request()->routeIs('hod.grading')">{{ __('Grading') }}</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('hod.registrations')" :active="request()->routeIs('hod.registrations')">{{ __('Registrations') }}</x-responsive-nav-link>
                @endcan

                {{-- Exams oversight (exam officer / MIS) --}}
                @can('view_exams')
                    <x-responsive-nav-link :href="route('exams.index')" :active="request()->routeIs('exams.index')">{{ __('Exams') }}</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('exams.queries')" :active="request()->routeIs('exams.queries')">{{ __('Result Queries') }}</x-responsive-nav-link>
                @endcan

                {{-- Finance --}}
                @can('view_fees')
                    <x-responsive-nav-link :href="route('fees.orders.index')" :active="request()->routeIs('fees.orders.*')">{{ __('Payment Orders') }}</x-responsive-nav-link>
                @endcan
                @can('manage_fees')
                    <x-responsive-nav-link :href="route('printables.index')" :active="request()->routeIs('printables.*')">{{ __('Printables') }}</x-responsive-nav-link>
                @endcan
                @if(in_array($role, ['bursar', 'mis']) && \Illuminate\Support\Facades\Route::has('payroll.index'))
                    <x-responsive-nav-link :href="route('payroll.index')" :active="request()->routeIs('payroll.*')">{{ __('HR / Payroll') }}</x-responsive-nav-link>
                @endif

                {{-- Timetable (all staff except view-only oversight) --}}
                @unless(in_array($role, ['proprietor', 'provost']))
                    <x-responsive-nav-link :href="route('timetable.index')" :active="request()->routeIs('timetable.*')">{{ __('Timetable') }}</x-responsive-nav-link>
                @endunless

                @can('view_library')
                    <x-responsive-nav-link :href="route('library.index')" :active="request()->routeIs('library.*')">{{ __('Library') }}</x-responsive-nav-link>
                @endcan
                @can('manage_announcements')
                    <x-responsive-nav-link :href="route('announcements.index')" :active="request()->routeIs('announcements.*')">{{ __('Announcements') }}</x-responsive-nav-link>
                @endcan

                @if(in_array($role, ['proprietor', 'provost', 'mis', 'office_secretary']))
                    <x-responsive-nav-link :href="route('inventory.index')" :active="request()->routeIs('inventory.*')">{{ __('Inventory') }}</x-responsive-nav-link>
                @endif

                @if(in_array($role, ['proprietor', 'mis', 'bursar']))
                    <x-responsive-nav-link :href="route('transport.index')" :active="request()->routeIs('transport.*')">{{ __('Transport') }}</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('alumni.index')" :active="request()->routeIs('alumni.*')">{{ __('Alumni') }}</x-responsive-nav-link>
                @endif
            @endif
        </nav>
    </aside>

</div>
