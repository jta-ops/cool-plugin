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
    $graph = $this->getGraphData();
    $graphRanges = $this->getGraphRanges();
    $graphPoints = $graph['points'] ?? [];
    $graphMax = max($graph['max'] ?? 1, 1);
    $graphWidth = 1000;
    $graphHeight = 280;
    $graphPadX = 44;
    $graphPadTop = 18;
    $graphPadBottom = 34;
    $graphInnerWidth = $graphWidth - ($graphPadX * 2);
    $graphInnerHeight = $graphHeight - $graphPadTop - $graphPadBottom;
    $linePoints = [];
    $areaPoints = [];
    $pointMeta = [];
    $pointCount = count($graphPoints);

    foreach ($graphPoints as $index => $point) {
        $x = $graphPadX + ($pointCount > 1 ? ($index / ($pointCount - 1)) * $graphInnerWidth : $graphInnerWidth / 2);
        $y = $graphPadTop + ($graphInnerHeight - (($point['count'] ?? 0) / $graphMax) * $graphInnerHeight);
        $linePoints[] = round($x, 2) . ',' . round($y, 2);
        $areaPoints[] = round($x, 2) . ',' . round($y, 2);
        $pointMeta[] = array_merge($point, ['x' => round($x, 2), 'y' => round($y, 2)]);
    }

    $areaPolygon = $pointCount > 0
        ? implode(' ', array_merge(["{$graphPadX}," . ($graphHeight - $graphPadBottom)], $areaPoints, [($graphWidth - $graphPadX) . ',' . ($graphHeight - $graphPadBottom)]))
        : '';

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

<div class="player-heatmap-ll-page" style="padding: 24px; max-width: 1200px; margin: 0 auto;">

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

    {{-- Optional Graph View --}}
    <div style="background: #0d1117; border-radius: 14px; border: 1px solid #30363d; padding: 20px; margin-bottom: 24px; box-shadow: inset 0 1px 0 rgba(255,255,255,0.02);">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
            <div>
                <h3 style="color: #e6edf3; font-size: 16px; font-weight: 600; margin: 0;">Graph View</h3>
                <p style="color: #8b949e; font-size: 12px; margin: 4px 0 0 0;">Player count over time from existing Minecraft history events.</p>
            </div>
            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                @foreach($graphRanges as $rangeKey => $rangeLabel)
                    <button
                        type="button"
                        wire:click="setGraphRange('{{ $rangeKey }}')"
                        style="background: {{ $graph['range'] === $rangeKey ? '#f97316' : '#161b22' }}; color: {{ $graph['range'] === $rangeKey ? '#fff7ed' : '#c9d1d9' }}; border: 1px solid {{ $graph['range'] === $rangeKey ? '#fb923c' : '#30363d' }}; border-radius: 999px; padding: 7px 11px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all .16s ease;"
                    >{{ $rangeLabel }}</button>
                @endforeach
            </div>
        </div>

        @if(!($graph['has_data'] ?? false) || $pointCount === 0)
            <div style="height: 260px; border-radius: 12px; border: 1px dashed #30363d; background: #090d13; display: flex; align-items: center; justify-content: center; color: #8b949e; font-size: 13px; text-align: center; padding: 24px;">
                No player history available.
            </div>
        @else
            <div class="player-history-graph" wire:key="player-history-graph-{{ $graph['range'] }}" style="position: relative; border-radius: 12px; background: linear-gradient(180deg, #0b0f14 0%, #090d13 100%); border: 1px solid #21262d; overflow: hidden; min-height: 260px;">
                <svg class="player-history-svg" viewBox="0 0 {{ $graphWidth }} {{ $graphHeight }}" preserveAspectRatio="none" data-points='@json($pointMeta)' style="display: block; width: 100%; height: clamp(240px, 34vw, 340px); touch-action: none;">
                    <defs>
                        <linearGradient id="playerHistoryFill-{{ $graph['range'] }}" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#f97316" stop-opacity="0.24" />
                            <stop offset="65%" stop-color="#ef4444" stop-opacity="0.08" />
                            <stop offset="100%" stop-color="#ef4444" stop-opacity="0" />
                        </linearGradient>
                        <filter id="playerHistoryGlow-{{ $graph['range'] }}" x="-20%" y="-80%" width="140%" height="260%">
                            <feGaussianBlur stdDeviation="3" result="blur" />
                            <feMerge>
                                <feMergeNode in="blur" />
                                <feMergeNode in="SourceGraphic" />
                            </feMerge>
                        </filter>
                    </defs>

                    @for($i = 0; $i <= 4; $i++)
                        @php
                            $gridY = $graphPadTop + ($i / 4) * $graphInnerHeight;
                            $gridValue = round($graphMax - (($i / 4) * $graphMax));
                        @endphp
                        <line x1="{{ $graphPadX }}" y1="{{ $gridY }}" x2="{{ $graphWidth - $graphPadX }}" y2="{{ $gridY }}" stroke="#30363d" stroke-opacity="0.36" stroke-width="1" />
                        <text x="12" y="{{ $gridY + 4 }}" fill="#6e7681" font-size="11">{{ $gridValue }}</text>
                    @endfor

                    @foreach([0, 0.25, 0.5, 0.75, 1] as $tick)
                        @php
                            $tickIndex = min($pointCount - 1, (int) round(($pointCount - 1) * $tick));
                            $tickX = $graphPadX + ($pointCount > 1 ? ($tickIndex / ($pointCount - 1)) * $graphInnerWidth : $graphInnerWidth / 2);
                            $tickLabel = $graphPoints[$tickIndex]['label'] ?? '';
                        @endphp
                        <text x="{{ $tickX }}" y="{{ $graphHeight - 10 }}" fill="#6e7681" font-size="11" text-anchor="middle">{{ $tickLabel }}</text>
                    @endforeach

                    <polygon points="{{ $areaPolygon }}" fill="url(#playerHistoryFill-{{ $graph['range'] }})" opacity="0">
                        <animate attributeName="opacity" from="0" to="1" dur="420ms" fill="freeze" />
                    </polygon>
                    <polyline points="{{ implode(' ', $linePoints) }}" fill="none" stroke="#f97316" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" filter="url(#playerHistoryGlow-{{ $graph['range'] }})" stroke-dasharray="1200" stroke-dashoffset="1200">
                        <animate attributeName="stroke-dashoffset" from="1200" to="0" dur="620ms" fill="freeze" />
                    </polyline>
                    <line class="graph-guide" x1="0" y1="{{ $graphPadTop }}" x2="0" y2="{{ $graphHeight - $graphPadBottom }}" stroke="#f97316" stroke-opacity="0" stroke-width="1.4" stroke-dasharray="4 5" />
                    <circle class="graph-dot" cx="0" cy="0" r="5" fill="#ffedd5" stroke="#f97316" stroke-width="2" opacity="0" />
                </svg>
                <div class="graph-tooltip" style="position: absolute; pointer-events: none; opacity: 0; transform: translate(-50%, -115%); background: rgba(13,17,23,.96); border: 1px solid #f97316; border-radius: 10px; padding: 9px 11px; box-shadow: 0 14px 34px rgba(0,0,0,.38); min-width: 150px; transition: opacity .12s ease;">
                    <div data-count style="color: #fff7ed; font-size: 18px; font-weight: 800; line-height: 1;"></div>
                    <div data-date style="color: #c9d1d9; font-size: 11px; margin-top: 6px;"></div>
                    <div data-time style="color: #8b949e; font-size: 11px; margin-top: 2px;"></div>
                </div>
            </div>
        @endif
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
        {{ config('player-heatmap-ll.brand_footer') }}
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

<script>
function initPlayerHistoryGraphs() {
    document.querySelectorAll('.player-history-graph').forEach((graph) => {
        if (graph.dataset.ready === '1') return;
        graph.dataset.ready = '1';

        const svg = graph.querySelector('.player-history-svg');
        const guide = graph.querySelector('.graph-guide');
        const dot = graph.querySelector('.graph-dot');
        const tooltip = graph.querySelector('.graph-tooltip');
        const countEl = tooltip?.querySelector('[data-count]');
        const dateEl = tooltip?.querySelector('[data-date]');
        const timeEl = tooltip?.querySelector('[data-time]');
        const points = JSON.parse(svg?.dataset.points || '[]');

        if (!svg || !guide || !dot || !tooltip || points.length === 0) return;

        const nearestPoint = (clientX) => {
            const rect = svg.getBoundingClientRect();
            const viewX = ((clientX - rect.left) / Math.max(rect.width, 1)) * {{ $graphWidth }};
            return points.reduce((nearest, point) => Math.abs(point.x - viewX) < Math.abs(nearest.x - viewX) ? point : nearest, points[0]);
        };

        const showPoint = (point) => {
            const rect = svg.getBoundingClientRect();
            const scaleX = rect.width / {{ $graphWidth }};
            const scaleY = rect.height / {{ $graphHeight }};
            const left = point.x * scaleX;
            const top = point.y * scaleY;

            guide.setAttribute('x1', point.x);
            guide.setAttribute('x2', point.x);
            guide.style.strokeOpacity = '0.85';
            dot.setAttribute('cx', point.x);
            dot.setAttribute('cy', point.y);
            dot.style.opacity = '1';

            countEl.textContent = `${point.count} ${point.count === 1 ? 'player' : 'players'}`;
            dateEl.textContent = point.date;
            timeEl.textContent = point.time;
            tooltip.style.left = `${left}px`;
            tooltip.style.top = `${Math.max(52, top)}px`;
            tooltip.style.opacity = '1';
        };

        const hidePoint = () => {
            guide.style.strokeOpacity = '0';
            dot.style.opacity = '0';
            tooltip.style.opacity = '0';
        };

        svg.addEventListener('pointermove', (event) => showPoint(nearestPoint(event.clientX)));
        svg.addEventListener('pointerleave', hidePoint);
        svg.addEventListener('pointerdown', (event) => showPoint(nearestPoint(event.clientX)));
    });
}

initPlayerHistoryGraphs();
document.addEventListener('livewire:navigated', initPlayerHistoryGraphs);
document.addEventListener('livewire:update', () => setTimeout(initPlayerHistoryGraphs, 0));
document.addEventListener('livewire:updated', () => setTimeout(initPlayerHistoryGraphs, 0));
</script>

<script>
(() => {
    const key = 'player-heatmap-ll-discord-dismissed';
    if (localStorage.getItem(key)) return;

    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(1,4,9,.72);z-index:99999;display:flex;align-items:center;justify-content:center;padding:18px;';
    overlay.innerHTML = `
        <div style="max-width:420px;width:100%;background:#161b22;border:1px solid #30363d;border-radius:14px;padding:22px;box-shadow:0 18px 60px rgba(0,0,0,.45);color:#e6edf3;font-family:inherit;">
            <div style="font-size:18px;font-weight:700;margin-bottom:8px;">Join Latitude Labs Discord</div>
            <div style="color:#8b949e;font-size:13px;line-height:1.5;margin-bottom:16px;">Get plugin updates, support, and Minecraft hosting help from Latitude Labs.</div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" data-close style="background:#21262d;color:#c9d1d9;border:1px solid #30363d;border-radius:8px;padding:8px 12px;cursor:pointer;">Not now</button>
                <a href="{{ config('player-heatmap-ll.discord_url') }}" target="_blank" rel="noopener" data-join style="background:#238636;color:#fff;text-decoration:none;border-radius:8px;padding:8px 12px;font-weight:600;">Join Discord</a>
            </div>
        </div>
    `;

    const close = () => {
        localStorage.setItem(key, '1');
        overlay.remove();
    };

    overlay.querySelector('[data-close]').addEventListener('click', close);
    overlay.querySelector('[data-join]').addEventListener('click', close);
    document.body.appendChild(overlay);
})();
</script>
