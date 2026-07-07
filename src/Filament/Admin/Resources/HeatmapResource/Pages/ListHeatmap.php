<?php

namespace James\CoolPlugin\Filament\Admin\Resources\HeatmapResource\Pages;

use App\Models\Server;
use Filament\Resources\Pages\ListRecords;
use James\CoolPlugin\Filament\Admin\Resources\HeatmapResource;
use James\CoolPlugin\Models\PlayerEvent;
use James\CoolPlugin\Models\PlayerHeatmapSample;

class ListHeatmap extends ListRecords
{
    protected static string $resource = HeatmapResource::class;

    protected string $view = 'Player-Heatmap-LL::pages.heatmap-dashboard';

    public function getHeading(): string
    {
        return 'Player Activity Heatmap';
    }

    public function getServerHeatmaps(): array
    {
        $servers = Server::all();
        $heatmaps = [];

        foreach ($servers as $server) {
            $data = PlayerHeatmapSample::getHeatmapForServer($server->id);
            $peak = PlayerHeatmapSample::getPeakInfoForServer($server->id);

            $heatmaps[] = [
                'server' => $server,
                'data' => $data,
                'peak' => $peak,
                'has_data' => PlayerHeatmapSample::serverHasData($server->id),
                'supported' => PlayerHeatmapSample::isMinecraftServer($server),
                'summary' => PlayerEvent::getActivitySummary($server->id),
            ];
        }

        return $heatmaps;
    }
}
