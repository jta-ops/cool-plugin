<?php

namespace James\CoolPlugin;

use App\Contracts\Plugins\HasPluginSettings;
use App\Enums\ConsoleWidgetPosition;
use App\Filament\Server\Pages\Console;
use App\Traits\EnvironmentWriterTrait;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Panel;
use James\CoolPlugin\Filament\Admin\Pages\HeatmapDashboard;
use James\CoolPlugin\Filament\Server\Pages\HeatmapPage;
use James\CoolPlugin\Filament\Server\Widgets\PlayerHeatmapWidget;

class CoolPluginPlugin implements HasPluginSettings, Plugin
{
    use EnvironmentWriterTrait;

    public function getId(): string
    {
        return 'Player-Heatmap-LL';
    }

    public function register(Panel $panel): void
    {
        // Register widgets on server panel console page
        if ($panel->getId() === 'server') {
            $position = match (config('Player-Heatmap-LL.widget_position', 'below_console')) {
                'top' => ConsoleWidgetPosition::Top,
                'above_console' => ConsoleWidgetPosition::AboveConsole,
                'below_console' => ConsoleWidgetPosition::BelowConsole,
                'bottom' => ConsoleWidgetPosition::Bottom,
                default => ConsoleWidgetPosition::BelowConsole,
            };

            Console::registerCustomWidgets($position, [
                PlayerHeatmapWidget::class,
            ]);
        }

        // Discover all Filament components per panel
        $id = str($panel->getId())->title();
        $panel->discoverPages(plugin_path($this->getId(), "src/Filament/$id/Pages"), "James\\CoolPlugin\\Filament\\$id\\Pages");
        $panel->discoverResources(plugin_path($this->getId(), "src/Filament/$id/Resources"), "James\\CoolPlugin\\Filament\\$id\\Resources");
        $panel->discoverWidgets(plugin_path($this->getId(), "src/Filament/$id/Widgets"), "James\\CoolPlugin\\Filament\\$id\\Widgets");
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public function getSettingsForm(): array
    {
        return [
            Select::make('widget_position')
                ->label('Widget position on console page')
                ->options([
                    'top' => 'Top',
                    'above_console' => 'Above Console',
                    'below_console' => 'Below Console (default)',
                    'bottom' => 'Bottom',
                ])
                ->default(config('Player-Heatmap-LL.widget_position', 'below_console'))
                ->native(false),

            Toggle::make('show_peak_indicator')
                ->label('Show peak time indicator')
                ->default(config('Player-Heatmap-LL.show_peak_indicator', true))
                ->inline(false),

            Toggle::make('show_stats_header')
                ->label('Show peak/average stats in widget header')
                ->default(config('Player-Heatmap-LL.show_stats_header', true))
                ->inline(false),
        ];
    }

    public function saveSettings(array $data): void
    {
        $this->writeToEnvironment([
            'PLAYER_HEATMAP_LL_WIDGET_POSITION' => $data['widget_position'],
            'PLAYER_HEATMAP_LL_SHOW_PEAK_INDICATOR' => $data['show_peak_indicator'] ? 'true' : 'false',
            'PLAYER_HEATMAP_LL_SHOW_STATS_HEADER' => $data['show_stats_header'] ? 'true' : 'false',
        ]);

        Notification::make()
            ->title('Heatmap settings saved!')
            ->success()
            ->send();
    }
}
