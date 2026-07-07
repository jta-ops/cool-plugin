<x-filament-panels::page>
    @php
        $heatmaps = $this->getServerHeatmaps();
        $dayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        function adminHeatColor($value, $max) {
            if ($value <= 0) return '#161b22';
            $intensity = min($value / max($max, 1), 1.0);
            $r = round(16 + (38 * $intensity));
            $g = round(22 + (175 * $intensity));
            $b = round(30 + (94 * $intensity));
            return "rgb($r, $g, $b)";
        }
    @endphp

    <div style="padding: 24px; max-width: 1400px; margin: 0 auto;">
        <div style="margin-bottom: 24px;">
            <h2 style="color: #e6edf3; font-size: 22px; font-weight: 600; margin: 0;">🔥 Player Activity Heatmap</h2>
            <p style="color: #8b949e; font-size: 13px; margin-top: 4px;">Overview of player activity across all servers</p>
        </div>

        @if($heatmaps && count($heatmaps) > 0)
            @foreach($heatmaps as $hm)
                @php
                    $server = $hm['server'];
                    $data = $hm['data'];
                    $peak = $hm['peak'];
                    $summary = $hm['summary'] ?? [];

                    $maxVal = 1;
                    foreach ($data as $hours) {
                        foreach ($hours as $val) {
                            if ($val > $maxVal) $maxVal = $val;
                        }
                    }
                @endphp

                <div style="background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 20px; margin-bottom: 16px;">
                    {{-- Header --}}
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                        <div>
                            <h3 style="color: #e6edf3; font-size: 16px; font-weight: 600; margin: 0;">
                                🎮 {{ $server->name }}
                            </h3>
                            <span style="color: #484f58; font-size: 11px;">{{ substr($server->uuid, 0, 8) }}</span>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <div style="text-align: center; background: #0d1117; padding: 6px 14px; border-radius: 8px; border: 1px solid #21262d;">
                                <div style="color: {{ $hm['supported'] ? '#3fb950' : '#f85149' }}; font-size: 12px; font-weight: 700; line-height: 1;">{{ $hm['supported'] ? 'Minecraft' : 'Skipped' }}</div>
                                <div style="color: #484f58; font-size: 9px; text-transform: uppercase;">egg</div>
                            </div>
                            <div style="text-align: center; background: #0d1117; padding: 6px 14px; border-radius: 8px; border: 1px solid #21262d;">
                                <div style="color: #58a6ff; font-size: 18px; font-weight: 700; line-height: 1;">{{ round($peak['peak_players'] ?? 0) }}</div>
                                <div style="color: #484f58; font-size: 9px; text-transform: uppercase;">Peak</div>
                            </div>
                            <div style="text-align: center; background: #0d1117; padding: 6px 14px; border-radius: 8px; border: 1px solid #21262d;">
                                <div style="color: #3fb950; font-size: 18px; font-weight: 700; line-height: 1;">{{ round($peak['avg_players'] ?? 0) }}</div>
                                <div style="color: #484f58; font-size: 9px; text-transform: uppercase;">Avg</div>
                            </div>
                        </div>
                    </div>

                    @if(!$hm['has_data'])
                        <div style="color: #8b949e; font-size: 13px; padding: 12px; background: #0d1117; border-radius: 8px; border: 1px dashed #30363d;">
                            {{ $hm['supported'] ? 'No real Minecraft log data yet. Waiting for join/leave lines.' : 'Not a detected Minecraft egg, so this server is skipped.' }}
                        </div>
                    @else
                        <div style="display: flex; gap: 8px; margin-bottom: 10px; color: #8b949e; font-size: 11px;">
                            <span>Online: {{ $summary['estimated_online'] ?? 0 }}</span>
                            <span>Unique: {{ $summary['unique_players'] ?? 0 }}</span>
                            <span>Joins: {{ $summary['total_joins'] ?? 0 }}</span>
                            <span>Leaves: {{ $summary['total_leaves'] ?? 0 }}</span>
                        </div>

                        {{-- Mini heatmap --}}
                        <div style="overflow-x: auto;">
                            <div style="display: flex; gap: 2px; margin-bottom: 3px;">
                                <div style="width: 32px;"></div>
                                @for($hour = 0; $hour < 24; $hour++)
                                    @if($hour % 3 === 0)
                                        <div style="flex: 1; text-align: center; color: #484f58; font-size: 9px; min-width: 14px;">{{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}</div>
                                    @else
                                        <div style="flex: 1; min-width: 14px;"></div>
                                    @endif
                                @endfor
                            </div>

                            @foreach($dayLabels as $dayIndex => $dayLabel)
                                <div style="display: flex; gap: 2px; margin-bottom: 2px;">
                                    <div style="width: 32px; color: #484f58; font-size: 10px; display: flex; align-items: center; justify-content: flex-end; padding-right: 4px;">{{ $dayLabel }}</div>
                                    @for($hour = 0; $hour < 24; $hour++)
                                        @php
                                            $val = $data[$dayIndex][$hour] ?? 0;
                                            $color = adminHeatColor($val, $maxVal);
                                            $isPeak = ($dayIndex === ($peak['peak_day'] ?? 0) && $hour === ($peak['peak_hour'] ?? 0));
                                            $border = $isPeak ? 'border:1.5px solid #58a6ff;' : '';
                                        @endphp
                                        <div
                                            style="flex:1;min-width:14px;height:16px;background-color:{{ $color }};border-radius:2px;position:relative;cursor:default;transition:transform 0.1s;{{ $border }}"
                                            title="{{ $dayLabel }} {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00 — {{ round($val) }} players{{ $isPeak ? ' ⭐ Peak' : '' }}"
                                            onmouseover="this.style.transform='scale(1.3)';this.style.zIndex='1';"
                                            onmouseout="this.style.transform='scale(1)';this.style.zIndex='0';"
                                        ></div>
                                    @endfor
                                </div>
                            @endforeach

                            {{-- Legend --}}
                            <div style="display: flex; align-items: center; gap: 4px; margin-top: 4px; margin-left: 34px;">
                                <span style="color: #484f58; font-size: 9px;">Less</span>
                                @for($i = 0; $i <= 4; $i++)
                                    @php $lc = adminHeatColor(($maxVal * $i) / 4, $maxVal); @endphp
                                    <div style="width:12px;height:8px;background-color:{{ $lc }};border-radius:2px;"></div>
                                @endfor
                                <span style="color: #484f58; font-size: 9px;">More</span>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        @else
            <div style="text-align: center; color: #8b949e; padding: 60px;">
                <h3>No servers found</h3>
                <p>Add servers to see heatmap data here.</p>
            </div>
        @endif

        <div style="text-align: center; color: #484f58; font-size: 11px; margin-top: 18px;">
            {{ config('cool-plugin.brand_footer') }}
        </div>
    </div>
</x-filament-panels::page>
