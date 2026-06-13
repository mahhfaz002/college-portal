@php $examCycle = auth()->check() ? \App\Models\ExamCycle::active() : null; @endphp
@if($examCycle)
    @php $isLecturer = auth()->user()->role === 'lecturer'; @endphp
    <div class="bg-gradient-to-r from-gray-900 to-indigo-900 text-white"
         x-data="examCountdown('{{ $examCycle->exam_start_at->toIso8601String() }}', '{{ $examCycle->submission_deadline_at->toIso8601String() }}')">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-wrap items-center gap-x-6 gap-y-2">
            <div class="flex items-center gap-2">
                <span class="text-lg">⏳</span>
                <span class="text-xs font-black uppercase tracking-wide">Exam Mode · {{ $examCycle->title }}</span>
            </div>

            {{-- General countdown: shown on every dashboard --}}
            <div class="flex items-center gap-2 text-sm">
                <span class="text-white/60 text-xs uppercase font-bold">Exams start in</span>
                <span class="font-mono font-bold" x-text="started ? 'Exams have started' : fmt(examStart)"></span>
                <span class="text-white/50 text-xs hidden sm:inline">({{ $examCycle->exam_start_at->format('D, d M Y g:ia') }})</span>
            </div>

            @if($isLecturer)
                {{-- Second timer: question-submission deadline (5 days before exams) --}}
                <div class="flex items-center gap-2 text-sm border-l border-white/20 pl-6">
                    <span class="text-amber-300 text-xs uppercase font-bold">Questions due in</span>
                    <span class="font-mono font-bold" :class="subClosed ? 'text-red-300' : 'text-amber-200'"
                          x-text="subClosed ? 'Submission closed' : fmt(subEnd)"></span>
                    <span class="text-white/50 text-xs hidden sm:inline">({{ $examCycle->submission_deadline_at->format('D, d M Y g:ia') }})</span>
                </div>
            @endif
        </div>
    </div>

    <script>
        function examCountdown(examStartIso, subEndIso) {
            return {
                examStart: new Date(examStartIso).getTime(),
                subEnd: new Date(subEndIso).getTime(),
                now: Date.now(),
                started: false, subClosed: false,
                init() {
                    this.tick();
                    setInterval(() => this.tick(), 1000);
                },
                tick() {
                    this.now = Date.now();
                    this.started = this.now >= this.examStart;
                    this.subClosed = this.now >= this.subEnd;
                },
                fmt(target) {
                    let ms = Math.max(0, target - this.now);
                    const d = Math.floor(ms / 86400000); ms -= d * 86400000;
                    const h = Math.floor(ms / 3600000); ms -= h * 3600000;
                    const m = Math.floor(ms / 60000); ms -= m * 60000;
                    const s = Math.floor(ms / 1000);
                    return `${d}d ${h}h ${m}m ${s}s`;
                },
            }
        }
    </script>
@endif
