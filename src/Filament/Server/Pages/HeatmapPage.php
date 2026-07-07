<?php

namespace James\CoolPlugin\Filament\Server\Pages;

use App\Models\Server;
use App\Traits\Filament\BlockAccessInConflict;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use James\CoolPlugin\Models\PlayerEvent;
use James\CoolPlugin\Models\PlayerHeatmapSample;

class HeatmapPage extends Page
{
    use BlockAccessInConflict;

    public string $graphRange = '24h';

    protected static \BackedEnum|string|null $navigationIcon = 'tabler-chart-bar';

    protected static ?string $slug = 'player-heatmap';

    protected static ?int $navigationSort = 31;

    protected static ?string $navigationLabel = 'Player Heatmap';

    protected string $view = 'player-heatmap-ll::pages.server-heatmap';

    public static function canAccess(): bool
    {
        return parent::canAccess();
    }

    public static function getNavigationLabel(): string
    {
        return 'Player Heatmap';
    }

    public function getTitle(): string
    {
        return 'Player Activity Heatmap';
    }

    public function getHeatmapData(): array
    {
        /** @var Server $server */
        $server = Filament::getTenant();

        $data = PlayerHeatmapSample::getHeatmapForServer($server->id);
        return $data;
    }

    public function getPeakInfo(): array
    {
        /** @var Server $server */
        $server = Filament::getTenant();

        return PlayerHeatmapSample::getPeakInfoForServer($server->id);
    }

    /**
     * Get players who were online at a specific day/hour slot.
     * Called via AJAX from the frontend.
     */
    public function getPlayersForSlot(int $day, int $hour): array
    {
        /** @var Server $server */
        $server = Filament::getTenant();

        $players = PlayerEvent::getPlayersAtTime($server->id, $day, $hour);

        return [
            'day' => $day,
            'hour' => $hour,
            'players' => $players,
            'count' => count($players),
        ];
    }

    /**
     * Get currently online players (from recent events).
     */
    public function getOnlinePlayers(): array
    {
        /** @var Server $server */
        $server = Filament::getTenant();

        return PlayerEvent::getRecentPlayers($server->id, 60);
    }

    public function getServerStatus(): array
    {
        /** @var Server $server */
        $server = Filament::getTenant();

        return [
            'name' => $server->name,
            'uuid' => $server->uuid,
            'status' => $server->status?->value ?? 'offline',
            'supported' => PlayerHeatmapSample::isMinecraftServer($server),
            'has_data' => PlayerHeatmapSample::serverHasData($server->id),
            'summary' => PlayerEvent::getActivitySummary($server->id),
        ];
    }

    public function setGraphRange(string $range): void
    {
        if (array_key_exists($range, $this->getGraphRanges())) {
            $this->graphRange = $range;
        }
    }

    public function getGraphRanges(): array
    {
        return [
            '24h' => '24 Hours',
            '7d' => '7 Days',
            '30d' => '30 Days',
            '3m' => '3 Months',
        ];
    }

    public function getGraphData(): array
    {
        /** @var Server $server */
        $server = Filament::getTenant();

        [$start, $stepMinutes, $format] = match ($this->graphRange) {
            '7d' => [now()->subDays(7), 120, 'M j, H:i'],
            '30d' => [now()->subDays(30), 720, 'M j, H:i'],
            '3m' => [now()->subMonths(3), 1440, 'M j'],
            default => [now()->subDay(), 30, 'H:i'],
        };

        $end = now();

        $latestBeforeStart = PlayerEvent::where('server_id', $server->id)
            ->where('event_at', '<', $start)
            ->orderBy('event_at', 'desc')
            ->get()
            ->unique('player_name');

        $onlinePlayers = [];
        foreach ($latestBeforeStart as $event) {
            if ($event->event === 'join') {
                $onlinePlayers[$event->player_name] = true;
            }
        }

        $events = PlayerEvent::where('server_id', $server->id)
            ->whereBetween('event_at', [$start, $end])
            ->orderBy('event_at')
            ->get();

        $points = [];
        $eventIndex = 0;
        $eventCount = $events->count();
        $cursor = $start->copy()->startOfMinute();

        while ($cursor <= $end) {
            while ($eventIndex < $eventCount && $events[$eventIndex]->event_at <= $cursor) {
                $event = $events[$eventIndex];
                if ($event->event === 'join') {
                    $onlinePlayers[$event->player_name] = true;
                } else {
                    unset($onlinePlayers[$event->player_name]);
                }
                $eventIndex++;
            }

            $points[] = [
                'count' => count($onlinePlayers),
                'date' => $cursor->format('M j, Y'),
                'time' => $cursor->format('H:i'),
                'label' => $cursor->format($format),
                'timestamp' => $cursor->toIso8601String(),
            ];

            $cursor->addMinutes($stepMinutes);
        }

        return [
            'range' => $this->graphRange,
            'points' => $points,
            'has_data' => $events->isNotEmpty() || count($onlinePlayers) > 0,
            'max' => max(1, collect($points)->max('count') ?? 1),
        ];
    }
}
