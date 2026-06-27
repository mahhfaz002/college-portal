@php
    $semesterStatus = auth()->check() ? setting('semester_status', 'active') : 'active';
    $nextSemesterStart = setting('next_semester_start');
    $nextSessionStart = setting('next_session_start');
@endphp
@if($semesterStatus === 'break' && $nextSemesterStart)
    <div class="bg-gradient-to-r from-amber-700 to-orange-700 text-white"
         x-data="semesterCountdown('{{ \Illuminate\Support\Carbon::parse($nextSemesterStart)->toIso8601String() }}'{{ $nextSessionStart ? ", '".\Illuminate\Support\Carbon::parse($nextSessionStart)->toIso8601String()."'" : '' }})">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-wrap items-center gap-x-6 gap-y-2">
            <div class="flex items-center gap-2">
                <span class="text-xs font-black uppercase tracking-wide">Semester Break</span>
            </div>
            <div class="flex items-center gap-2 text-sm">
                <span class="text-white/60 text-xs uppercase font-bold">Next semester starts in</span>
                <span class="font-mono font-bold" x-text="started ? 'Starting now…' : fmt(semStart)"></span>
                <span class="text-white/50 text-xs hidden sm:inline">({{ \Illuminate\Support\Carbon::parse($nextSemesterStart)->format('D, d M Y') }})</span>
            </div>
            @if($nextSessionStart)
            <div class="flex items-center gap-2 text-sm border-l border-white/20 pl-6">
                <span class="text-white/60 text-xs uppercase font-bold">New session begins</span>
                <span class="font-mono font-bold">{{ \Illuminate\Support\Carbon::parse($nextSessionStart)->format('D, d M Y') }}</span>
            </div>
            @endif
        </div>
    </div>

    <script>
        function semesterCountdown(semStartIso) {
            return {
                semStart: new Date(semStartIso).getTime(),
                now: Date.now(),
                started: false,
                init() { this.tick(); setInterval(() => this.tick(), 1000); },
                tick() { this.now = Date.now(); this.started = this.now >= this.semStart; },
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
