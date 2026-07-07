<?php

namespace James\CoolPlugin\Filament\Server\Widgets;

use App\Models\Server;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use James\CoolPlugin\Models\PlayerEvent;
use James\CoolPlugin\Models\PlayerHeatmapSample;

class PlayerHeatmapWidget extends Widget
{
    protected string $view = 'player-heatmap-ll::widgets.player-heatmap';

    protected static ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 100;

    public ?Server $server = null;

    public static function canView(): bool
    {
        /** @var Server $server */
        $server = Filament::getTenant();

        return !$server->isInConflictState();
    }

    public function getHeading(): string
    {
        return trans('player-heatmap-ll::heatmap.heading');
    }

    public function getHeatmapData(): array
    {
        $serverId = $this->getServerId();

        if ($serverId) {
            return PlayerHeatmapSample::getHeatmapForServer($serverId);
        }

        return array_fill(0, 7, array_fill(0, 24, 0));
    }

    public function getPeakInfo(): array
    {
        $serverId = $this->getServerId();
        if (!$serverId) {
            return ['peak_hour' => 0, 'peak_day' => 0, 'peak_players' => 0, 'avg_players' => 0, 'total_data_points' => 0];
        }

        return PlayerHeatmapSample::getPeakInfoForServer($serverId);
    }

    public function getStatus(): array
    {
        $server = Filament::getTenant();
        if (!$server) {
            return ['supported' => false, 'has_data' => false, 'message' => 'No server context'];
        }

        $supported = PlayerHeatmapSample::isMinecraftServer($server);
        $hasData = PlayerHeatmapSample::serverHasData($server->id);

        return [
            'supported' => $supported,
            'has_data' => $hasData,
            'summary' => PlayerEvent::getActivitySummary($server->id),
            'message' => $supported
                ? ($hasData ? 'Live Minecraft log data' : 'Waiting for Minecraft join/leave log lines')
                : 'Only Minecraft eggs are supported',
        ];
    }

    protected function getServerId(): ?int
    {
        try {
            return Filament::getTenant()?->id;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getDayLabels(): array
    {
        return ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    }

    public function getHourLabels(): array
    {
        return range(0, 23);
    }
}
