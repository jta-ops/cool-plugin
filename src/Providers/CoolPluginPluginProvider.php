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
            plugin_path('cool-plugin', 'config/cool-plugin.php'),
            'cool-plugin'
        );
    }

    public function boot(): void
    {
        // Load views
        $this->loadViewsFrom(
            plugin_path('cool-plugin', 'resources/views'),
            'cool-plugin'
        );

        // Load translations
        $this->loadTranslationsFrom(
            plugin_path('cool-plugin', 'lang'),
            'cool-plugin'
        );

        // Load migrations
        $this->loadMigrationsFrom(
            plugin_path('cool-plugin', 'database/migrations')
        );

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CollectPlayerCounts::class,
                ScanLogs::class,
            ]);
        }

        // Schedule: collect player counts every minute
        Schedule::command('cool-plugin:collect')->everyMinute();

        // Schedule: scan logs every 5 minutes
        Schedule::command('cool-plugin:scan-logs')->everyFiveMinutes();
    }
}