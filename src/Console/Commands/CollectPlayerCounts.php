<?php

namespace James\CoolPlugin\Console\Commands;

use App\Models\Server;
use Illuminate\Console\Command;
use James\CoolPlugin\Models\PlayerEvent;
use James\CoolPlugin\Models\PlayerHeatmapSample;

class CollectPlayerCounts extends Command
{
    protected $signature = 'cool-plugin:collect
                            {server? : Specific server ID to collect for}
                            {--all : Collect for all servers including offline}';

    protected $description = 'Collect player counts for the heatmap widget';

    public function handle(): int
    {
        $serverId = $this->argument('server');

        if ($serverId) {
            $servers = Server::where('id', $serverId)->get();
        } else {
            $query = Server::query();

            // Skip offline/suspended servers unless --all
            if (!$this->option('all')) {
                $query->whereNull('status');
            }

            $servers = $query->get();
        }

        if ($servers->isEmpty()) {
            $this->info('No servers found to collect data for.');
            return static::SUCCESS;
        }

        $collected = 0;
        $skipped = 0;

        foreach ($servers as $server) {
            try {
                if (!PlayerHeatmapSample::isMinecraftServer($server)) {
                    $skipped++;
                    continue;
                }

                $playerCount = $this->getPlayerCount($server);

                if ($playerCount < 0) {
                    $skipped++;
                    continue;
                }

                $now = now();
                $dayOfWeek = $now->dayOfWeekIso - 1; // 0=Mon, 6=Sun
                $hour = $now->hour;

                PlayerHeatmapSample::recordSample(
                    $server->id,
                    $dayOfWeek,
                    $hour,
                    $playerCount,
                    (float) config('cool-plugin.sample_alpha', 0.3)
                );

                $collected++;
            } catch (\Throwable $e) {
                $this->warn("Failed to collect for server {$server->id}: {$e->getMessage()}");
                $skipped++;
            }
        }

        $this->info("Collected player counts for {$collected} servers (skipped {$skipped}).");
        return static::SUCCESS;
    }

    /**
     * Get the current player count from this plugin's own Minecraft log events.
     */
    protected function getPlayerCount(Server $server): int
    {
        if ($server->isInConflictState() || ($server->status && method_exists($server->status, 'isOffline') && $server->status->isOffline())) {
            return -1; // Signal to skip
        }

        return PlayerEvent::getEstimatedOnlineCount($server->id);
    }
}
