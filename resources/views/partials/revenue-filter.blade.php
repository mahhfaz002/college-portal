{{--
    Revenue timeframe filter. Expects $revenueRange (from App\Support\RevenueRange).
    Optional $dark = true for placement on a dark finance banner.
--}}
@php
    $rr = $revenueRange ?? ['preset' => 'all', 'from' => null, 'to' => null];
    $dark = $dark ?? false;
    $base = $dark ? 'bg-white/10 text-white border-white/20' : 'bg-white text-gray-700 border-gray-300';
@endphp
<form method="GET" class="flex flex-wrap items-end gap-2"
      x-data="{ preset: '{{ $rr['preset'] }}' }">
    {{-- Preserve other query params (e.g. month) --}}
    @foreach(request()->except(['range', 'from', 'to', 'page']) as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
    @endforeach
    <div>
        <label class="block text-[10px] font-bold uppercase {{ $dark ? 'text-white/60' : 'text-gray-400' }} mb-1">Timeframe</label>
        <select name="range" x-model="preset" onchange="this.form.requestSubmit()"
                class="rounded-md text-sm {{ $base }}">
            @foreach(\App\Support\RevenueRange::PRESETS as $key => $label)
                <option value="{{ $key }}" {{ $rr['preset'] === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div x-show="preset === 'custom'" x-cloak class="flex items-end gap-2">
        <div>
            <label class="block text-[10px] font-bold uppercase {{ $dark ? 'text-white/60' : 'text-gray-400' }} mb-1">From</label>
            <input type="date" name="from" value="{{ $rr['from'] }}" class="rounded-md text-sm {{ $base }}">
        </div>
        <div>
            <label class="block text-[10px] font-bold uppercase {{ $dark ? 'text-white/60' : 'text-gray-400' }} mb-1">To</label>
            <input type="date" name="to" value="{{ $rr['to'] }}" class="rounded-md text-sm {{ $base }}">
        </div>
        <button class="px-3 py-2 rounded-md text-sm font-bold {{ $dark ? 'bg-white text-gray-900' : 'bg-gray-800 text-white' }}">Apply</button>
    </div>
</form>
