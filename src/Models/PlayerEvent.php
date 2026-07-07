<?php

namespace James\CoolPlugin\Models;

use App\Models\Server;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerEvent extends Model
{
    protected $fillable = [
        'server_id',
        'player_name',
        'event',
        'event_at',
        'source_hash',
        'source_path',
    ];

    protected $casts = [
        'event_at' => 'datetime',
        'server_id' => 'integer',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Record a player join/leave event.
     */
    public static function recordEvent(int $serverId, string $playerName, string $event, ?string $eventAt = null, ?string $sourceHash = null, ?string $sourcePath = null): self
    {
        if ($sourceHash) {
            $existing = static::where('server_id', $serverId)->where('source_hash', $sourceHash)->first();
            if ($existing) {
                return $existing;
            }
        }

        return static::create([
            'server_id' => $serverId,
            'player_name' => $playerName,
            'event' => $event,
            'event_at' => $eventAt ?? now(),
            'source_hash' => $sourceHash,
            'source_path' => $sourcePath,
        ]);
    }

    public static function getEstimatedOnlineCount(int $serverId, ?\Carbon\CarbonInterface $at = null): int
    {
        $at ??= now();

        $latestEvents = static::where('server_id', $serverId)
            ->where('event_at', '<=', $at)
            ->orderBy('event_at', 'desc')
            ->get()
            ->unique('player_name');

        return $latestEvents->where('event', 'join')->count();
    }

    public static function getActivitySummary(int $serverId): array
    {
        $joins = static::where('server_id', $serverId)->where('event', 'join');
        $leaves = static::where('server_id', $serverId)->where('event', 'leave');
        $lastEvent = static::where('server_id', $serverId)->latest('event_at')->first();

        return [
            'total_joins' => (clone $joins)->count(),
            'total_leaves' => (clone $leaves)->count(),
            'unique_players' => static::where('server_id', $serverId)->distinct('player_name')->count('player_name'),
            'estimated_online' => static::getEstimatedOnlineCount($serverId),
            'last_activity_at' => $lastEvent?->event_at,
        ];
    }

    /**
     * Get players who were online at a specific time on a specific day/hour.
     * A player is considered online at time T if:
     *   - They had a 'join' event before T
     *   - Their next 'leave' event (if any) is after T
     */
    public static function getPlayersAtTime(int $serverId, int $dayOfWeek, int $hour): array
    {
        // Get a representative datetime for this slot (most recent occurrence)
        $now = now();
        $daysAgo = ($now->dayOfWeekIso - 1) - $dayOfWeek; // 0=Mon
        if ($daysAgo < 0) $daysAgo += 7;

        $slotTime = $now->copy()->subDays($daysAgo)->setTime($hour, 0, 0);
        $slotStart = $slotTime->copy()->subHours(1);
        $slotEnd = $slotTime->copy()->addHour();

        // Find joins in the window around this slot
        $joins = static::where('server_id', $serverId)
            ->where('event', 'join')
            ->where('event_at', '<=', $slotEnd)
            ->orderBy('event_at', 'desc')
            ->get()
            ->unique('player_name');

        $players = [];
        foreach ($joins as $join) {
            // Check if there's a leave after this join and before our slot end
            $leave = static::where('server_id', $serverId)
                ->where('player_name', $join->player_name)
                ->where('event', 'leave')
                ->where('event_at', '>', $join->event_at)
                ->where('event_at', '<=', $slotEnd)
                ->orderBy('event_at', 'asc')
                ->first();

            if (!$leave || $leave->event_at > $slotTime) {
                $players[] = $join->player_name;
            }
        }

        return $players;
    }

    /**
     * Get players online in the last N minutes for a server.
     */
    public static function getRecentPlayers(int $serverId, int $minutes = 60): array
    {
        $since = now()->subMinutes($minutes);

        // Get most recent events per player
        $recentJoins = static::where('server_id', $serverId)
            ->where('event', 'join')
            ->where('event_at', '>=', $since)
            ->orderBy('event_at', 'desc')
            ->get()
            ->unique('player_name');

        $players = [];
        foreach ($recentJoins as $join) {
            // Check if they're still online (no leave event after the join)
            $leave = static::where('server_id', $serverId)
                ->where('player_name', $join->player_name)
                ->where('event', 'leave')
                ->where('event_at', '>', $join->event_at)
                ->orderBy('event_at', 'asc')
                ->first();

            if (!$leave) {
                $players[] = [
                    'name' => $join->player_name,
                    'joined_at' => $join->event_at->format('H:i'),
                ];
            }
        }

        return $players;
    }

    /**
     * Clean up old events (older than 30 days).
     */
    public static function cleanup(int $days = 30): int
    {
        return static::where('event_at', '<', now()->subDays($days))->delete();
    }
}
