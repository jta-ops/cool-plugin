<?php

namespace James\CoolPlugin\Console\Commands;

use App\Models\Server;
use App\Repositories\Daemon\DaemonFileRepository;
use Illuminate\Console\Command;
use James\CoolPlugin\Models\PlayerEvent;
use James\CoolPlugin\Models\PlayerHeatmapSample;

class ScanLogs extends Command
{
    protected $signature = 'player-heatmap-ll:scan-logs
                            {server? : Specific server ID}
                            {--all : Scan all servers including offline}';

    protected $description = 'Scan server logs for player join/leave events and update heatmap data';

    // Minecraft join/leave patterns only. This plugin intentionally avoids other plugin dependencies.
    protected array $patterns = [
        'iso_join' => '/^(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})\s+\[.*?\]:?\s+(.+?) joined the game/i',
        'iso_leave' => '/^(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})\s+\[.*?\]:?\s+(.+?) left the game/i',
        'bracket_join' => '/^\[(\d{2}:\d{2}:\d{2})\]\s+\[.*?\]:?\s+(.+?) joined the game/i',
        'bracket_leave' => '/^\[(\d{2}:\d{2}:\d{2})\]\s+\[.*?\]:?\s+(.+?) left the game/i',
        'plain_join' => '/(?:^|\]:?\s+)(.+?) joined the game/i',
        'plain_leave' => '/(?:^|\]:?\s+)(.+?) left the game/i',
    ];

    public function handle(): int
    {
        $serverId = $this->argument('server');

        if ($serverId) {
            $servers = Server::where('id', $serverId)->get();
        } else {
            $servers = Server::all();
        }

        if ($servers->isEmpty()) {
            $this->info('No servers found.');
            return static::SUCCESS;
        }

        $scanned = 0;
        $events = 0;

        foreach ($servers as $server) {
            try {
                if (!PlayerHeatmapSample::isMinecraftServer($server)) {
                    continue;
                }

                $count = $this->scanServer($server);
                $events += $count;
                $scanned++;
            } catch (\Throwable $e) {
                $this->warn("Failed to scan server {$server->id} ({$server->name}): {$e->getMessage()}");
            }
        }

        // Also collect current counts estimated from this plugin's own parsed events.
        $this->callSilent('player-heatmap-ll:collect', ['--all' => true]);

        // Cleanup old events (older than 30 days)
        $deleted = PlayerEvent::cleanup(30);
        if ($deleted > 0) {
            $this->info("Cleaned up {$deleted} old events.");
        }

        $this->info("Scanned {$scanned} servers, found {$events} player events.");
        return static::SUCCESS;
    }

    protected function scanServer(Server $server): int
    {
        $fileRepo = (new DaemonFileRepository())->setServer($server);

        $logPaths = config('player-heatmap-ll.log_paths', [
            'logs/latest.log',
            'server.log',
            'console.log',
            'logs/debug.log',
        ]);

        $logContent = null;
        $sourcePath = null;
        foreach ($logPaths as $path) {
            try {
                $content = $fileRepo->getContent($path);
                if ($content && strlen($content) > 0) {
                    $logContent = $content;
                    $sourcePath = $path;
                    break;
                }
            } catch (\Throwable $e) {
                // File doesn't exist or not readable, try next
                continue;
            }
        }

        if (!$logContent) {
            return 0;
        }

        $lines = explode("\n", $logContent);
        $eventCount = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Extract timestamp and event type
            $result = $this->parseLine($line);
            if (!$result) continue;

            [$eventType, $playerName, $eventTime] = $result;

            $lineHash = hash('sha256', ($sourcePath ?? 'unknown') . '|' . $line);
            if (PlayerEvent::where('server_id', $server->id)->where('source_hash', $lineHash)->exists()) continue;

            // Skip if event is too old (> 7 days)
            if ($eventTime && $eventTime->isBefore(now()->subDays(7))) continue;

            $eventTimeStr = $eventTime ? $eventTime->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s');

            // Check for duplicate (same player, same event, same source time window)
            $recentDuplicate = PlayerEvent::where('server_id', $server->id)
                ->where('player_name', $playerName)
                ->where('event', $eventType)
                ->whereBetween('event_at', [($eventTime ?? now())->copy()->subMinutes(2), ($eventTime ?? now())->copy()->addMinutes(2)])
                ->exists();

            if (!$recentDuplicate) {
                PlayerEvent::recordEvent(
                    $server->id,
                    $playerName,
                    $eventType,
                    $eventTimeStr,
                    $lineHash,
                    $sourcePath
                );
                $this->recordHeatmapFromEvent($server, $eventTime ?? now());
                $eventCount++;
            }
        }

        return $eventCount;
    }

    protected function parseLine(string $line): ?array
    {
        if (preg_match($this->patterns['iso_join'], $line, $m)) {
            $time = $this->parseTimestamp($m[1]);
            return ['join', $m[2], $time];
        }
        if (preg_match($this->patterns['iso_leave'], $line, $m)) {
            $time = $this->parseTimestamp($m[1]);
            return ['leave', $m[2], $time];
        }

        if (preg_match($this->patterns['bracket_join'], $line, $m)) {
            return ['join', $m[2], $this->parseTimestamp(now()->format('Y-m-d') . ' ' . $m[1])];
        }
        if (preg_match($this->patterns['bracket_leave'], $line, $m)) {
            return ['leave', $m[2], $this->parseTimestamp(now()->format('Y-m-d') . ' ' . $m[1])];
        }

        if (preg_match($this->patterns['plain_join'], $line, $m)) {
            return ['join', trim($m[1]), null];
        }
        if (preg_match($this->patterns['plain_leave'], $line, $m)) {
            return ['leave', trim($m[1]), null];
        }

        return null;
    }

    protected function parseTimestamp(string $ts): ?\Carbon\Carbon
    {
        try {
            return \Carbon\Carbon::parse($ts);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function recordHeatmapFromEvent(Server $server, \Carbon\CarbonInterface $eventTime): void
    {
        PlayerHeatmapSample::recordSample(
            $server->id,
            $eventTime->dayOfWeekIso - 1,
            $eventTime->hour,
            PlayerEvent::getEstimatedOnlineCount($server->id, $eventTime),
            (float) config('player-heatmap-ll.sample_alpha', 0.3)
        );
    }
}
