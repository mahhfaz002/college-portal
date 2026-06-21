{{-- resources/views/emails/incident-report.blade.php --}}
<div style="font-family:system-ui,-apple-system,sans-serif;max-width:560px;color:#1a1a2e;line-height:1.5">
    <h2 style="color:#2d4a7c;margin-bottom:4px">College Portal — Auto-Fix Report</h2>
    <p style="color:#64748b;margin-top:0;font-size:14px">
        Incident #{{ $prNumber }} · {{ now()->toDayDateTimeString() }}
    </p>

    <div style="background:#f4f6fb;border-radius:10px;padding:16px 18px;white-space:pre-wrap">{{ $summary }}</div>

    @if($prUrl)
        <p style="font-size:13px;color:#64748b;margin-top:16px">
            Technical details:
            <a href="{{ $prUrl }}" style="color:#2d4a7c">View pull request #{{ $prNumber }}</a>
        </p>
    @endif
</div>
