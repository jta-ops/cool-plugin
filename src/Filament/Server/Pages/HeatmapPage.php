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

    protected static \BackedEnum|string|null $navigationIcon = 'tabler-chart-bar';

    protected static ?string $slug = 'player-heatmap';

    protected static ?int $navigationSort = 31;

    protected static ?string $navigationLabel = 'Player Heatmap';

    protected string $view = 'Player-Heatmap-LL::pages.server-heatmap';

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
}
