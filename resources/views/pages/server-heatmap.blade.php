@php
    $heatmapData = $this->getHeatmapData();
    $peakInfo = $this->getPeakInfo();
    $serverStatus = $this->getServerStatus();
    $dayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $fullDayLabels = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    $maxVal = 1;
    foreach ($heatmapData as $hours) {
        foreach ($hours as $val) {
            if ($val > $maxVal) $maxVal = $val;
        }
    }

    $hasData = ($peakInfo['total_data_points'] ?? 0) > 0;
    $summary = $serverStatus['summary'] ?? [];

    function heatColor($value, $max) {
        if ($value <= 0) return '#161b22';
        $intensity = min($value / max($max, 1), 1.0);
        $r = round(16 + (38 * $intensity));
        $g = round(22 + (175 * $intensity));
        $b = round(30 + (94 * $intensity));
        return "rgb($r, $g, $b)";
    }

    $peakDay = $peakInfo['peak_day'] ?? 5;
    $peakHour = $peakInfo['peak_hour'] ?? 20;
@endphp

<div class="cool-plugin-heatmap-page" style="padding: 24px; max-width: 1200px; margin: 0 auto;">

    {{-- Header --}}
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding: 16px; background: #161b22; border-radius: 12px; border: 1px solid #30363d;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #238636, #2ea043); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                🔥
            </div>
            <div>
                <h2 style="color: #e6edf3; font-size: 18px; font-weight: 600; margin: 0;">
                    Player Activity — {{ $serverStatus['name'] }}
                </h2>
                <span style="color: #8b949e; font-size: 12px;">UUID: {{ substr($serverStatus['uuid'], 0, 8) }}...</span>
            </div>
        </div>
        <div style="display: flex; gap: 12px;">
            <div style="text-align: center; background: #0d1117; padding: 10px 16px; border-radius: 10px; border: 1px solid #21262d; min-width: 80px;">
                <div style="color: #58a6ff; font-size: 24px; font-weight: 700; line-height: 1;">{{ round($peakInfo['peak_players'] ?? 0) }}</div>
                <div style="color: #484f58; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px;">Peak</div>
            </div>
            <div style="text-align: center; background: #0d1117; padding: 10px 16px; border-radius: 10px; border: 1px solid #21262d; min-width: 80px;">
                <div style="color: #3fb950; font-size: 24px; font-weight: 700; line-height: 1;">{{ round($peakInfo['avg_players'] ?? 0) }}</div>
                <div style="color: #484f58; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px;">Average</div>
            </div>
        </div>
    </div>

    @if(!$serverStatus['supported'])
        <div style="padding: 10px 16px; margin-bottom: 16px; background: #161b22; border-radius: 8px; border: 1px dashed #30363d; color: #8b949e; font-size: 13px;">
            This plugin only supports Minecraft eggs. Rename or tag the egg with a Minecraft keyword if this is a Minecraft server.
        </div>
    @elseif(!$hasData)
        <div style="padding: 10px 16px; margin-bottom: 16px; background: #161b22; border-radius: 8px; border: 1px dashed #30363d; color: #8b949e; font-size: 13px;">
            Waiting for real Minecraft join/leave log lines. No demo or fake activity is shown.
        </div>
    @else
        <div style="padding: 10px 16px; margin-bottom: 16px; background: #0d2818; border-radius: 8px; border: 1px solid #1b4332; color: #3fb950; font-size: 13px;">
            Live Minecraft log data — collected {{ number_format($peakInfo['total_data_points'] ?? 0) }} samples so far.
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px;">
        <div style="background: #161b22; border: 1px solid #30363d; border-radius: 10px; padding: 12px;">
            <div style="color: #58a6ff; font-size: 20px; font-weight: 700;">{{ $summary['estimated_online'] ?? 0 }}</div>
            <div style="color: #8b949e; font-size: 11px;">estimated online</div>
        </div>
        <div style="background: #161b22; border: 1px solid #30363d; border-radius: 10px; padding: 12px;">
            <div style="color: #3fb950; font-size: 20px; font-weight: 700;">{{ $summary['unique_players'] ?? 0 }}</div>
            <div style="color: #8b949e; font-size: 11px;">unique players</div>
        </div>
        <div style="background: #161b22; border: 1px solid #30363d; border-radius: 10px; padding: 12px;">
            <div style="color: #d29922; font-size: 20px; font-weight: 700;">{{ $summary['total_joins'] ?? 0 }}</div>
            <div style="color: #8b949e; font-size: 11px;">joins recorded</div>
        </div>
        <div style="background: #161b22; border: 1px solid #30363d; border-radius: 10px; padding: 12px;">
            <div style="color: #f85149; font-size: 20px; font-weight: 700;">{{ $summary['total_leaves'] ?? 0 }}</div>
            <div style="color: #8b949e; font-size: 11px;">leaves recorded</div>
        </div>
    </div>

    {{-- Main Heatmap Grid --}}
    <div style="background: #161b22; border-radius: 12px; border: 1px solid #30363d; padding: 24px; margin-bottom: 24px;">
        <h3 style="color: #e6edf3; font-size: 16px; font-weight: 600; margin: 0 0 16px 0;">
            Activity by Hour
        </h3>

        <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;" id="heatmap-grid">
            {{-- Hour labels --}}
            <div style="display: flex; gap: 3px; margin-bottom: 6px;">
                <div style="width: 48px;"></div>
                @for($hour = 0; $hour < 24; $hour++)
                    @if($hour % 2 === 0)
                        <div style="flex: 1; text-align: center; color: #8b949e; font-size: 11px; min-width: 28px; font-weight: 500;">
                            {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00
                        </div>
                    @else
                        <div style="flex: 1; min-width: 28px;"></div>
                    @endif
                @endfor
            </div>

            {{-- Day rows --}}
            @foreach($dayLabels as $dayIndex => $dayLabel)
                <div style="display: flex; gap: 3px; margin-bottom: 3px;">
                    <div style="width: 48px; color: #8b949e; font-size: 12px; display: flex; align-items: center; justify-content: flex-end; padding-right: 8px; font-weight: 500;">
                        {{ $dayLabel }}
                    </div>
                    @for($hour = 0; $hour < 24; $hour++)
                        @php
                            $val = $heatmapData[$dayIndex][$hour] ?? 0;
                            $color = heatColor($val, $maxVal);
                            $isPeak = ($dayIndex === $peakDay && $hour === $peakHour);
                            $borderStyle = $isPeak ? 'border: 2px solid #58a6ff; box-shadow: 0 0 8px rgba(88,166,255,0.4);' : 'border: 1px solid #21262d;';
                        @endphp
                        <div
                            class="heatmap-cell"
                            data-day="{{ $dayIndex }}"
                            data-hour="{{ $hour }}"
                            data-val="{{ round($val) }}"
                            data-day-name="{{ $fullDayLabels[$dayIndex] }}"
                            style="
                                flex: 1; min-width: 28px; height: 30px;
                                background-color: {{ $color }};
                                {{ $borderStyle }}
                                border-radius: 4px;
                                position: relative; cursor: pointer;
                                transition: all 0.12s ease;
                            "
                            title="{{ $fullDayLabels[$dayIndex] }} {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00 — {{ round($val) }} players{{ $isPeak ? ' ⭐ Peak' : '' }}"
                            onmouseover="this.style.transform='scale(1.2)';this.style.zIndex='10';this.style.boxShadow='0 0 12px rgba(88,166,255,0.6)';"
                            onmouseout="this.style.transform='scale(1)';this.style.zIndex='0';this.style.boxShadow='{{ $isPeak ? '0 0 8px rgba(88,166,255,0.4)' : 'none' }}';"
                            onclick="showDetail(this)"
                        ></div>
                    @endfor
                </div>
            @endforeach

            {{-- Legend --}}
            <div style="display: flex; align-items: center; gap: 6px; margin-top: 12px; margin-left: 52px;">
                <span style="color: #8b949e; font-size: 11px;">Fewer</span>
                @for($i = 0; $i <= 5; $i++)
                    @php $legendColor = heatColor(($maxVal * $i) / 5, $maxVal); @endphp
                    <div style="width: 20px; height: 12px; background-color: {{ $legendColor }}; border-radius: 3px;"></div>
                @endfor
                <span style="color: #8b949e; font-size: 11px;">More</span>
                <span style="margin-left: 16px; color: #484f58; font-size: 11px;">⭐ Peak time &nbsp;•&nbsp; Click any cell for details</span>
            </div>
        </div>
    </div>

    {{-- Detail panel (shown on cell click) --}}
    <div id="detail-panel" style="display: none; background: #161b22; border-radius: 12px; border: 1px solid #30363d; padding: 24px; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <h3 style="color: #e6edf3; font-size: 16px; font-weight: 600; margin: 0;">
                <span id="detail-title">Monday at 20:00</span>
            </h3>
            <button onclick="document.getElementById('detail-panel').style.display='none'" style="background: #21262d; color: #8b949e; border: 1px solid #30363d; border-radius: 6px; padding: 6px 14px; cursor: pointer; font-size: 12px; font-weight: 500;">
                ✕ Close
            </button>
        </div>
        <div id="detail-content" style="color: #c9d1d9; font-size: 14px;"></div>
    </div>

    {{-- Per-Day Bar Chart --}}
    <div style="background: #161b22; border-radius: 12px; border: 1px solid #30363d; padding: 24px;">
        <h3 style="color: #e6edf3; font-size: 16px; font-weight: 600; margin: 0 0 16px 0;">
            Average by Day
        </h3>
        @php
            $dayTotals = [];
            foreach ($heatmapData as $day => $hours) {
                $dayTotals[$day] = array_sum($hours) / 24;
            }
            $maxDayAvg = max($dayTotals);
        @endphp
        <div style="display: flex; gap: 8px; align-items: flex-end; height: 130px;">
            @foreach($dayLabels as $dayIndex => $dayLabel)
                @php
                    $avg = $dayTotals[$dayIndex] ?? 0;
                    $height = $maxDayAvg > 0 ? ($avg / $maxDayAvg) * 100 : 0;
                    $color = heatColor($avg, $maxDayAvg);
                @endphp
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%;">
                    <div style="flex: 1; display: flex; align-items: flex-end; width: 100%;">
                        <div style="
                            width: 100%;
                            height: {{ max($height, 3) }}%;
                            background-color: {{ $color }};
                            border-radius: 4px 4px 0 0;
                            transition: height 0.3s ease;
                            min-height: 4px;
                        " title="{{ $fullDayLabels[$dayIndex] }} — avg {{ round($avg) }} players"></div>
                    </div>
                    <div style="color: #8b949e; font-size: 11px; margin-top: 6px; font-weight: 500;">{{ $dayLabel }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div style="text-align: center; color: #484f58; font-size: 11px; margin-top: 18px;">
        {{ config('cool-plugin.brand_footer') }}
    </div>
</div>

<script>
function showDetail(cell) {
    const day = cell.dataset.day;
    const hour = cell.dataset.hour;
    const val = cell.dataset.val;
    const dayName = cell.dataset.dayName;
    const hourStr = String(hour).padStart(2, '0') + ':00';
    const playerWord = val === '1' ? 'player' : 'players';

    document.getElementById('detail-title').textContent = dayName + ' at ' + hourStr;

    document.getElementById('detail-content').innerHTML = `
        <div style="display: flex; gap: 24px; align-items: center;">
            <div>
                <div style="font-size: 48px; font-weight: 700; color: #3fb950;">${val}</div>
                <div style="color: #8b949e; font-size: 12px;">${playerWord} on average</div>
            </div>
            <div style="color: #484f58; font-size: 13px; line-height: 2;">
                <div>📅 ${dayName}</div>
                <div>🕐 ${hourStr}</div>
                <div>👥 ${val} ${playerWord}</div>
            </div>
        </div>
    `;

    document.getElementById('detail-panel').style.display = 'block';
}
</script>
