<?php

namespace James\CoolPlugin\Filament\Admin\Resources;

use Filament\Resources\Resource;
use James\CoolPlugin\Filament\Admin\Resources\HeatmapResource\Pages\ListHeatmap;

class HeatmapResource extends Resource
{
    protected static ?string $model = \James\CoolPlugin\Models\PlayerHeatmapSample::class;

    protected static \BackedEnum|string|null $navigationIcon = 'tabler-chart-bar';

    protected static ?string $navigationLabel = 'Player Heatmap';

    protected static ?string $slug = 'player-heatmap';

    protected static ?string $title = 'Player Activity Heatmap';

    protected static ?int $navigationSort = 100;

    public static function getNavigationGroup(): string
    {
        return 'Analytics';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHeatmap::route('/'),
        ];
    }
}