@php
    $heatmapData = $this->getHeatmapData();
    $peakInfo = $this->getPeakInfo();
    $status = $this->getStatus();
    $dayLabels = $this->getDayLabels();
    $hourLabels = $this->getHourLabels();

    // Find max for color scaling
    $maxVal = 1;
    foreach ($heatmapData as $hours) {
        foreach ($hours as $val) {
            if ($val > $maxVal) $maxVal = $val;
        }
    }

    function heatColor($value, $max) {
        if ($value <= 0) return '#161b22';
        $intensity = min($value / max($max, 1), 1.0);
        // GitHub-style green gradient
        $r = round(16 + (38 * $intensity));
        $g = round(22 + (175 * $intensity));
        $b = round(30 + (94 * $intensity));
        return "rgb($r, $g, $b)";
    }

    $peakDay = $peakInfo['peak_day'] ?? 5;
    $peakHour = $peakInfo['peak_hour'] ?? 20;
    $hasData = ($peakInfo['total_data_points'] ?? 0) > 0;
@endphp

<div class="cool-plugin-heatmap-widget" style="padding: 16px;">
    {{-- Header with stats --}}
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 16px;">🔥</span>
            <h3 style="color: #e6edf3; font-size: 14px; font-weight: 600; margin: 0;">
                {{ $this->getHeading() }}
            </h3>
            @if(!$status['supported'])
                <span style="background: #1c2128; color: #8b949e; font-size: 10px; padding: 2px 8px; border-radius: 10px; border: 1px solid #30363d;">
                    Minecraft only
                </span>
            @elseif(!$hasData)
                <span style="background: #1c2128; color: #8b949e; font-size: 10px; padding: 2px 8px; border-radius: 10px; border: 1px solid #30363d;">
                    waiting for logs
                </span>
            @endif
        </div>
        <div style="display: flex; gap: 10px;">
            <div style="text-align: center; background: #0d1117; padding: 6px 12px; border-radius: 8px; border: 1px solid #21262d;">
                <div style="color: #58a6ff; font-size: 16px; font-weight: 700; line-height: 1;">{{ round($peakInfo['peak_players'] ?? 0) }}</div>
                <div style="color: #484f58; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px;">peak</div>
            </div>
            <div style="text-align: center; background: #0d1117; padding: 6px 12px; border-radius: 8px; border: 1px solid #21262d;">
                <div style="color: #3fb950; font-size: 16px; font-weight: 700; line-height: 1;">{{ round($peakInfo['avg_players'] ?? 0) }}</div>
                <div style="color: #484f58; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px;">avg</div>
            </div>
        </div>
    </div>

    {{-- Heatmap Grid --}}
    <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
        {{-- Hour labels --}}
        <div style="display: flex; gap: 2px; margin-bottom: 3px;">
            <div style="width: 32px;"></div>
            @foreach($hourLabels as $hour)
                @if($hour % 3 === 0)
                    <div style="flex: 1; text-align: center; color: #484f58; font-size: 9px; min-width: 16px;">
                        {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}
                    </div>
                @else
                    <div style="flex: 1; min-width: 16px;"></div>
                @endif
            @endforeach
        </div>

        {{-- Rows --}}
        @foreach($dayLabels as $dayIndex => $dayLabel)
            <div style="display: flex; gap: 2px; margin-bottom: 2px;">
                <div style="width: 32px; color: #484f58; font-size: 10px; display: flex; align-items: center; justify-content: flex-end; padding-right: 4px;">
                    {{ $dayLabel }}
                </div>
                @for($hour = 0; $hour < 24; $hour++)
                    @php
                        $val = $heatmapData[$dayIndex][$hour] ?? 0;
                        $color = heatColor($val, $maxVal);
                        $isPeak = ($dayIndex === $peakDay && $hour === $peakHour);
                        $borderStyle = $isPeak ? 'border: 1.5px solid #58a6ff;' : '';
                    @endphp
                    <div
                        style="
                            flex: 1; min-width: 16px; height: 18px;
                            background-color: {{ $color }};
                            border-radius: 2px;
                            position: relative; cursor: default;
                            transition: all 0.12s ease;
                            {{ $borderStyle }}
                        "
                        title="{{ $dayLabel }} {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00 — {{ round($val) }} players{{ $isPeak ? ' ⭐ Peak' : '' }}"
                        onmouseover="this.style.transform='scale(1.5)';this.style.zIndex='10';this.style.boxShadow='0 0 10px rgba(88,166,255,0.6)';"
                        onmouseout="this.style.transform='scale(1)';this.style.zIndex='0';this.style.boxShadow='none';"
                    ></div>
                @endfor
            </div>
        @endforeach

        {{-- Legend --}}
        <div style="display: flex; align-items: center; gap: 4px; margin-top: 6px; margin-left: 35px;">
            <span style="color: #484f58; font-size: 9px;">Less</span>
            @for($i = 0; $i <= 4; $i++)
                @php $legendColor = heatColor(($maxVal * $i) / 4, $maxVal); @endphp
                <div style="width: 14px; height: 10px; background-color: {{ $legendColor }}; border-radius: 2px;"></div>
            @endfor
            <span style="color: #484f58; font-size: 9px;">More</span>
        </div>

        {{-- Peak time info --}}
        @if(!empty($peakInfo) && ($peakInfo['peak_players'] ?? 0) > 0)
            <div style="margin-top: 8px; margin-left: 35px; color: #484f58; font-size: 10px;">
                ⭐ Peak: {{ $dayLabels[$peakDay] ?? '' }} at {{ str_pad($peakHour, 2, '0', STR_PAD_LEFT) }}:00
                ({{ round($peakInfo['peak_players']) }} players)
            </div>
        @endif

        @if(!$hasData)
            <div style="margin-top: 8px; margin-left: 35px; color: #8b949e; font-size: 10px;">
                {{ $status['message'] }}. This widget only uses real Minecraft join/leave log data.
            </div>
        @endif

        <div style="margin-top: 8px; margin-left: 35px; color: #484f58; font-size: 9px;">
            {{ config('cool-plugin.brand_footer') }}
        </div>
    </div>
</div>
