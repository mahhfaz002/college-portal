{{-- resources/views/emails/daily-digest.blade.php --}}
<div style="font-family:system-ui,-apple-system,sans-serif;max-width:560px;color:#1a1a2e;line-height:1.5">
    <h2 style="color:#2d4a7c;margin-bottom:4px">College Portal — Daily Status</h2>
    <p style="color:#64748b;margin-top:0;font-size:14px">{{ now()->toDayDateTimeString() }}</p>

    <p style="font-weight:600;color:{{ $healthy ? '#15803d' : '#b91c1c' }}">
        {{ $healthy ? '● System healthy' : '● Needs attention' }}
    </p>

    <div style="background:#f4f6fb;border-radius:10px;padding:16px 18px;white-space:pre-wrap">{{ $summary }}</div>
</div>
