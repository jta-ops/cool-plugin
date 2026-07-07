<?php

namespace James\CoolPlugin\Models;

use App\Models\Server;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerHeatmapSample extends Model
{
    protected $fillable = [
        'server_id',
        'day_of_week',
        'hour',
        'player_count',
        'sample_count',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'day_of_week' => 'integer',
        'hour' => 'integer',
        'player_count' => 'integer',
        'sample_count' => 'integer',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Get the average player count (player_count / sample_count).
     */
    public function getAverageAttribute(): float
    {
        return $this->sample_count > 0
            ? round($this->player_count / $this->sample_count, 1)
            : 0;
    }

    /**
     * Record a sample: incrementally update the running average.
     * Uses exponential moving average so recent data matters more.
     */
    public static function recordSample(int $serverId, int $dayOfWeek, int $hour, int $playerCount, float $alpha = 0.3): void
    {
        $sample = static::firstOrNew([
            'server_id' => $serverId,
            'day_of_week' => $dayOfWeek,
            'hour' => $hour,
        ]);

        if ($sample->exists && $sample->sample_count > 0) {
            // EMA: new_avg = old_avg * (1-alpha) + new_val * alpha
            $oldAvg = $sample->player_count / $sample->sample_count;
            $newAvg = ($oldAvg * (1 - $alpha)) + ($playerCount * $alpha);
            $sample->player_count = (int) round($newAvg * ($sample->sample_count + 1));
            $sample->sample_count += 1;
        } else {
            $sample->player_count = $playerCount;
            $sample->sample_count = 1;
        }

        $sample->save();
    }

    /**
     * Get heatmap data for a server as a 7x24 array.
     */
    public static function getHeatmapForServer(int $serverId): array
    {
        $rows = static::where('server_id', $serverId)->get()->keyBy(fn ($r) => "{$r->day_of_week}.{$r->hour}");

        $data = [];
        for ($day = 0; $day < 7; $day++) {
            $data[$day] = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $key = "{$day}.{$hour}";
                $row = $rows->get($key);
                $data[$day][$hour] = $row ? round($row->player_count / max($row->sample_count, 1), 1) : 0;
            }
        }

        return $data;
    }

    /**
     * Get peak info for a server: peak hour, peak day, peak player count.
     */
    public static function getPeakInfoForServer(int $serverId): array
    {
        $data = static::getHeatmapForServer($serverId);

        $peakHour = 0;
        $peakDay = 0;
        $peakVal = 0;

        foreach ($data as $day => $hours) {
            foreach ($hours as $hour => $val) {
                if ($val > $peakVal) {
                    $peakVal = $val;
                    $peakDay = $day;
                    $peakHour = $hour;
                }
            }
        }

        // Calculate averages
        $totalSamples = 0;
        $totalPlayers = 0;
        $nonZeroHours = 0;
        foreach ($data as $hours) {
            foreach ($hours as $val) {
                if ($val > 0) {
                    $totalPlayers += $val;
                    $nonZeroHours++;
                }
            }
        }

        return [
            'peak_hour' => $peakHour,
            'peak_day' => $peakDay,
            'peak_players' => round($peakVal),
            'avg_players' => $nonZeroHours > 0 ? round($totalPlayers / $nonZeroHours, 1) : 0,
            'total_data_points' => static::where('server_id', $serverId)->sum('sample_count'),
        ];
    }

    public static function serverHasData(int $serverId): bool
    {
        return static::where('server_id', $serverId)->where('sample_count', '>', 0)->exists();
    }

    public static function isMinecraftServer(Server $server): bool
    {
        $haystack = strtolower(implode(' ', array_filter([
            $server->name ?? '',
            $server->startup ?? '',
            $server->image ?? '',
            $server->egg?->name ?? '',
            $server->egg?->description ?? '',
            implode(' ', $server->egg?->tags ?? []),
        ])));

        foreach (config('player-heatmap-ll.minecraft_keywords', []) as $keyword) {
            if ($keyword !== '' && str_contains($haystack, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }
}
