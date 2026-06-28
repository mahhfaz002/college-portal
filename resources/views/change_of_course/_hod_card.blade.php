<div class="bg-white rounded-2xl shadow-sm border p-6 space-y-3 mb-3" x-data="{ decision: '' }">
    <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
        <div>
            <b>{{ optional($r->student)->full_name }}</b>
            <span class="text-gray-400">·</span> {{ optional($r->student)->registration_number ?? optional($r->student)->admission_number }}
            <div class="mt-1">
                <b>{{ optional($r->currentProgram)->name ?? '—' }}</b>
                <span class="text-gray-400">→</span>
                <b class="text-indigo-700">{{ optional($r->requestedProgram)->name }}</b>
            </div>
        </div>
        <span class="text-[10px] uppercase font-bold px-2 py-1 rounded {{ $context === 'incoming' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
            {{ $context === 'incoming' ? 'Joining my department' : 'Leaving my department' }}
        </span>
    </div>

    <p class="text-xs text-gray-500">Reason: {{ $r->reason }}</p>
    @if($r->secretary_comment)
        <div class="text-xs p-2 rounded bg-gray-50 border"><b>Academic Secretary:</b> {{ $r->secretary_comment }}</div>
    @endif

    <a href="{{ route('change-of-course.credentials', $r) }}" class="inline-block text-xs font-bold text-indigo-600 hover:underline">View student credentials →</a>

    <form method="POST" action="{{ route('change-of-course.hod-decide', $r) }}" class="border-t pt-3 space-y-2">
        @csrf
        <div class="grid sm:grid-cols-2 gap-2">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Decision</label>
                <select name="decision" x-model="decision" required class="w-full border-gray-300 rounded-lg text-sm">
                    <option value="">— Select —</option>
                    <option value="accept">Accept</option>
                    <option value="reject">Reject</option>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Comment <span x-show="decision==='reject'" class="text-red-500">(reason for rejection)</span></label>
            <textarea name="comment" rows="2" required class="w-full border-gray-300 rounded-lg text-sm" placeholder="Your comment — this is recorded and, on rejection, cited back to the student."></textarea>
        </div>
        <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-indigo-700">Submit Decision</button>
    </form>
</div>
