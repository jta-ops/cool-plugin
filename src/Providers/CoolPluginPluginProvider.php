<?php

namespace James\CoolPlugin\Providers;

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use James\CoolPlugin\Console\Commands\CollectPlayerCounts;
use James\CoolPlugin\Console\Commands\ScanLogs;

class CoolPluginPluginProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            plugin_path('Player-Heatmap-LL', 'config/cool-plugin.php'),
            'Player-Heatmap-LL'
        );
    }

    public function boot(): void
    {
        // Load views
        $this->loadViewsFrom(
            plugin_path('Player-Heatmap-LL', 'resources/views'),
            'Player-Heatmap-LL'
        );

        // Load translations
        $this->loadTranslationsFrom(
            plugin_path('Player-Heatmap-LL', 'lang'),
            'Player-Heatmap-LL'
        );

        // Load migrations
        $this->loadMigrationsFrom(
            plugin_path('Player-Heatmap-LL', 'database/migrations')
        );

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CollectPlayerCounts::class,
                ScanLogs::class,
            ]);
        }

        // Schedule: collect player counts every minute
        Schedule::command('player-heatmap-ll:collect')->everyMinute();

        // Schedule: scan logs every 5 minutes
        Schedule::command('player-heatmap-ll:scan-logs')->everyFiveMinutes();
    }
}
