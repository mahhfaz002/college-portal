<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Resolves a finance-overview timeframe filter (yesterday / 7d / 30d / year /
 * custom date range) from the request into a [start, end] window plus a label.
 * Shared by every dashboard that shows a revenue figure so the behaviour and
 * the UI option set stay identical everywhere.
 */
class RevenueRange
{
    /** preset key => human label. 'all' means no date constraint. */
    public const PRESETS = [
        'all'       => 'All time',
        'yesterday' => 'Yesterday',
        '7d'        => 'Last 7 days',
        '30d'       => 'Last 30 days',
        'year'      => 'Last year',
        'custom'    => 'Date range',
    ];

    /**
     * @return array{start: ?CarbonImmutable, end: ?CarbonImmutable, preset: string, label: string, from: ?string, to: ?string}
     */
    public static function fromRequest(Request $request): array
    {
        $preset = $request->query('range', 'all');
        if (!array_key_exists($preset, self::PRESETS)) {
            $preset = 'all';
        }

        $now = CarbonImmutable::now();
        $start = null;
        $end = null;
        $from = $request->query('from');
        $to = $request->query('to');

        switch ($preset) {
            case 'yesterday':
                $start = $now->subDay()->startOfDay();
                $end = $now->subDay()->endOfDay();
                break;
            case '7d':
                $start = $now->subDays(7)->startOfDay();
                $end = $now->endOfDay();
                break;
            case '30d':
                $start = $now->subDays(30)->startOfDay();
                $end = $now->endOfDay();
                break;
            case 'year':
                $start = $now->subYear()->startOfDay();
                $end = $now->endOfDay();
                break;
            case 'custom':
                try {
                    $start = $from ? CarbonImmutable::parse($from)->startOfDay() : null;
                    $end = $to ? CarbonImmutable::parse($to)->endOfDay() : null;
                } catch (\Throwable $e) {
                    $start = $end = null;
                    $preset = 'all';
                }
                break;
        }

        return [
            'start'  => $start,
            'end'    => $end,
            'preset' => $preset,
            'label'  => self::PRESETS[$preset],
            'from'   => $from,
            'to'     => $to,
        ];
    }

    /**
     * Apply the resolved window to an Invoice query on the paid_at column.
     * (paid_at is only set on settled invoices, which is exactly what revenue means.)
     */
    public static function apply($query, array $range, string $column = 'paid_at')
    {
        return $query
            ->when($range['start'], fn ($q) => $q->where($column, '>=', $range['start']))
            ->when($range['end'], fn ($q) => $q->where($column, '<=', $range['end']));
    }
}
