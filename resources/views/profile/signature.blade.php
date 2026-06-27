<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">My E-Signature</h2></x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))<div class="p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg text-sm"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

            {{-- Current signature --}}
            @if($user->signature_path)
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-gray-700">Current Signature</h3>
                    <form method="POST" action="{{ route('signature.destroy') }}" onsubmit="return confirm('Remove your signature?')">
                        @csrf @method('DELETE')
                        <button class="text-red-600 text-xs font-bold hover:underline">Remove</button>
                    </form>
                </div>
                <div class="border rounded-lg p-4 bg-gray-50 inline-block">
                    <img src="{{ route('signature.show', $user) }}" alt="Signature" class="h-20 object-contain">
                </div>
            </div>
            @endif

            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b font-bold text-gray-700">Set / Update Signature</div>
                <form method="POST" action="{{ route('signature.update') }}" enctype="multipart/form-data" class="p-6 space-y-6" id="sigForm">
                    @csrf

                    {{-- Option 1: Upload PNG --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Option 1 — Upload a PNG (transparent background recommended)</label>
                        <input type="file" name="signature_file" accept="image/png"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="text-xs text-gray-400 mt-1">PNG only, max 2MB. A signature with no background looks best on documents.</p>
                    </div>

                    <div class="text-center text-xs font-bold text-gray-400">— OR —</div>

                    {{-- Option 2: Draw --}}
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Option 2 — Draw your signature</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg bg-white">
                            <canvas id="sigPad" width="600" height="180" class="w-full touch-none rounded-lg"></canvas>
                        </div>
                        <div class="flex gap-2 mt-2">
                            <button type="button" onclick="clearPad()" class="text-xs font-bold text-gray-600 hover:underline">Clear</button>
                        </div>
                        <input type="hidden" name="signature_data" id="sigData">
                    </div>

                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-full font-bold hover:bg-indigo-700">Save Signature</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const canvas = document.getElementById('sigPad');
        const ctx = canvas.getContext('2d');
        let drawing = false, hasDrawn = false;
        ctx.lineWidth = 2.5; ctx.lineCap = 'round'; ctx.strokeStyle = '#111827';

        function pos(e) {
            const r = canvas.getBoundingClientRect();
            const cx = (e.touches ? e.touches[0].clientX : e.clientX) - r.left;
            const cy = (e.touches ? e.touches[0].clientY : e.clientY) - r.top;
            return { x: cx * (canvas.width / r.width), y: cy * (canvas.height / r.height) };
        }
        function start(e){ drawing = true; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
        function move(e){ if(!drawing) return; const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); hasDrawn = true; e.preventDefault(); }
        function end(){ drawing = false; }
        canvas.addEventListener('mousedown', start); canvas.addEventListener('mousemove', move);
        window.addEventListener('mouseup', end);
        canvas.addEventListener('touchstart', start); canvas.addEventListener('touchmove', move);
        canvas.addEventListener('touchend', end);

        function clearPad(){ ctx.clearRect(0,0,canvas.width,canvas.height); hasDrawn = false; document.getElementById('sigData').value=''; }

        document.getElementById('sigForm').addEventListener('submit', function(){
            if (hasDrawn) { document.getElementById('sigData').value = canvas.toDataURL('image/png'); }
        });
    </script>
</x-app-layout>
