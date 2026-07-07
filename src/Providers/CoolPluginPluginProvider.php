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
            plugin_path('player-heatmap-ll', 'config/cool-plugin.php'),
            'player-heatmap-ll'
        );
    }

    public function boot(): void
    {
        // Load views
        $this->loadViewsFrom(
            plugin_path('player-heatmap-ll', 'resources/views'),
            'player-heatmap-ll'
        );

        // Load translations
        $this->loadTranslationsFrom(
            plugin_path('player-heatmap-ll', 'lang'),
            'player-heatmap-ll'
        );

        // Load migrations
        $this->loadMigrationsFrom(
            plugin_path('player-heatmap-ll', 'database/migrations')
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
