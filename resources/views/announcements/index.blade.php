<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">📢 Announcements</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
            @endif

            @if($canManage)
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4">Post a new announcement</h3>

                    @if($role === 'student_affairs')
                        {{-- Student Affairs: students only, filterable by department / course / level. --}}
                        <form method="POST" action="{{ route('announcements.store') }}" class="space-y-3" x-data="{ scope: 'all' }">
                            @csrf
                            <input name="title" placeholder="Title" required class="w-full rounded-lg border-gray-300">
                            <textarea name="body" placeholder="Message..." rows="3" required class="w-full rounded-lg border-gray-300"></textarea>
                            <p class="text-xs text-gray-400">This notice goes to <strong>students</strong>. Choose who exactly:</p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Send to</label>
                                    <select x-model="scope" class="w-full rounded-lg border-gray-300">
                                        <option value="all">All students</option>
                                        <option value="department">By department</option>
                                        <option value="program">By course of study</option>
                                        <option value="level">By level</option>
                                    </select>
                                </div>
                                <template x-if="scope === 'department'">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Department</label>
                                        <select name="target_department_id" class="w-full rounded-lg border-gray-300">
                                            <option value="">Select department…</option>
                                            @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                                        </select>
                                    </div>
                                </template>
                                <template x-if="scope === 'program'">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Course of Study</label>
                                        <select name="target_program_id" class="w-full rounded-lg border-gray-300">
                                            <option value="">Select course…</option>
                                            @foreach($programs as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                                        </select>
                                    </div>
                                </template>
                                <template x-if="scope === 'level'">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Level</label>
                                        <select name="target_level" class="w-full rounded-lg border-gray-300">
                                            <option value="">Select level…</option>
                                            @foreach($levels as $l)<option value="{{ $l }}">Level {{ $l }}</option>@endforeach
                                        </select>
                                    </div>
                                </template>
                                <button class="text-white px-5 py-2 rounded-lg font-bold h-10" style="background: var(--brand)">Post</button>
                            </div>
                        </form>
                    @else
                        <form method="POST" action="{{ route('announcements.store') }}" class="space-y-3">
                            @csrf
                            <input name="title" placeholder="Title" required class="w-full rounded-lg border-gray-300">
                            <textarea name="body" placeholder="Message..." rows="3" required class="w-full rounded-lg border-gray-300"></textarea>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Send to</label>
                                    <select name="audience" id="audienceSelect" class="w-full rounded-lg border-gray-300">
                                        <option value="all">Everyone</option>
                                        <option value="staff">Staff only</option>
                                        <option value="students">Students only</option>
                                        <option value="both">Staff &amp; Students</option>
                                        <option value="class">A specific programme</option>
                                    </select>
                                </div>
                                <div id="classWrap" style="display:none">
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Programme</label>
                                    <select name="target_class" class="w-full rounded-lg border-gray-300">
                                        <option value="">Select programme…</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class }}">{{ $class }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="text-white px-5 py-2 rounded-lg font-bold h-10" style="background: var(--brand)">Post</button>
                            </div>
                        </form>
                        <script>
                            document.getElementById('audienceSelect')?.addEventListener('change', function () {
                                document.getElementById('classWrap').style.display = this.value === 'class' ? 'block' : 'none';
                            });
                        </script>
                    @endif
                </div>
            @endif

            <div class="space-y-4">
                @forelse($announcements as $a)
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-bold text-gray-800">{{ $a->title }}</h4>
                                <p class="text-xs text-gray-400">
                                    {{ $a->author->name ?? 'System' }} • {{ $a->created_at->diffForHumans() }}
                                    <span class="ml-2 px-2 py-0.5 bg-gray-100 rounded-full uppercase">{{ $a->audience === 'class' ? $a->target_class : $a->audience }}</span>
                                </p>
                            </div>
                            @if($canManage && ($a->user_id === auth()->id() || auth()->user()->role === 'mis'))
                                <form method="POST" action="{{ route('announcements.destroy', $a) }}" onsubmit="return confirm('Delete this announcement?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 text-xs font-bold hover:underline">Delete</button>
                                </form>
                            @endif
                        </div>
                        <p class="mt-2 text-gray-600 whitespace-pre-line">{{ $a->body }}</p>
                    </div>
                @empty
                    <p class="text-center text-gray-400 italic py-8">No announcements yet.</p>
                @endforelse
            </div>

            {{ $announcements->links() }}
        </div>
    </div>
</x-app-layout>
