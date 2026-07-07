# Player-Heatmap-LL

Real Minecraft player activity analytics for Pelican.

Player-Heatmap-LL is a self-contained Pelican plugin by Latitude Labs that reads Minecraft server logs, records real join and leave events, and turns that activity into useful heatmaps and server summaries. It does not require any other plugin or external player counter.

Made by latitudehost.uk.

## What It Does

- Shows a Minecraft activity heatmap by day and hour.
- Adds a compact heatmap widget to the server console page.
- Adds a full server activity page with detailed stats.
- Adds an admin overview across all supported Minecraft servers.
- Tracks joins, leaves, unique players, estimated online players, peak activity, and average activity.
- Uses only real Minecraft log data. It does not show fake demo activity.
- Skips non-Minecraft eggs automatically.
- Shows a one-time Latitude Labs Discord popup on the console widget or heatmap page.

## Why This Exists

Most player activity widgets depend on query ports, game-specific APIs, or another plugin being installed first. This plugin is designed to work from the data Minecraft already writes: server logs.

Install it once, let the scheduler run, and activity starts appearing as players join and leave.

## Requirements

- Pelican panel compatible with this plugin's `panel_version`.
- Wings file access for the target server logs.
- Minecraft eggs that write normal join and leave messages.
- Laravel scheduler running for Pelican.

## Supported Servers

The plugin is intentionally Minecraft-only.

It detects Minecraft servers using egg and server metadata such as:

- Egg name
- Egg description
- Egg tags
- Server startup command
- Server image
- Server name

Default Minecraft keywords:

```text
minecraft, paper, spigot, purpur, forge, fabric, vanilla, bukkit, neoforge
```

If a real Minecraft egg is skipped, add one of those words to the egg name, description, tags, startup command, image, or configure custom keywords.

## Log Files Checked

The scanner checks these paths through Wings file access:

```text
logs/latest.log
server.log
console.log
logs/debug.log
```

Standard Minecraft lines are supported, including:

```text
[12:34:56] [Server thread/INFO]: Steve joined the game
[12:58:10] [Server thread/INFO]: Steve left the game
2026-07-07 12:34:56 [Server thread/INFO]: Alex joined the game
2026-07-07 12:58:10 [Server thread/INFO]: Alex left the game
```

## Installation

1. Install the plugin through Pelican's plugin system.
2. Run Pelican migrations if your install flow does not do this automatically.
3. Make sure the Laravel scheduler is running.
4. Start or keep running a Minecraft server.
5. Wait for players to join and leave.

The plugin includes its own migrations, commands, views, config, and scheduler entries.

## Scheduled Collection

The plugin registers two scheduled tasks:

```text
player-heatmap-ll:collect    every minute
player-heatmap-ll:scan-logs  every five minutes
```

`scan-logs` reads Minecraft logs and records join/leave events.

`collect` estimates the current online count from those events and stores heatmap samples.

## Manual Commands

Scan all Minecraft servers:

```bash
php artisan player-heatmap-ll:scan-logs --all
```

Scan one server:

```bash
php artisan player-heatmap-ll:scan-logs SERVER_ID
```

Collect current estimated counts:

```bash
php artisan player-heatmap-ll:collect --all
```

Collect one server:

```bash
php artisan player-heatmap-ll:collect SERVER_ID
```

## Configuration

Widget position:

```env
PLAYER_HEATMAP_LL_WIDGET_POSITION=below_console
```

Supported values:

```text
top
above_console
below_console
bottom
```

Minecraft detection keywords:

```env
PLAYER_HEATMAP_LL_MINECRAFT_KEYWORDS=minecraft,paper,spigot,purpur,forge,fabric,vanilla,bukkit,neoforge
```

Custom log paths, added in this version:

```env
PLAYER_HEATMAP_LL_LOG_PATHS=logs/latest.log,server.log,console.log,logs/debug.log
```

Sample smoothing weight:

```env
PLAYER_HEATMAP_LL_SAMPLE_ALPHA=0.3
```

Higher values react faster to new activity. Lower values smooth activity over time.

## Data Accuracy

This plugin estimates activity from logs. That makes it simple and self-contained, but there are some natural limits:

- If old logs rotate away before scanning, those events cannot be read.
- If a server crashes, some leave events may be missing.
- If logs are deleted, activity history cannot be recovered from them.
- If custom server software changes join/leave wording, those lines may not parse.

For normal Minecraft logs, the plugin provides a useful activity heatmap without query ports or external integrations.

## Troubleshooting

### Heatmap Is Empty

Check that:

- The server uses a detected Minecraft egg.
- Players have joined and left since the plugin was installed.
- `logs/latest.log` or another supported log path exists.
- Wings can read the server files.
- The Laravel scheduler is running.

You can also run:

```bash
php artisan player-heatmap-ll:scan-logs --all
```

### Server Says Minecraft Only

The egg was not detected as Minecraft. Add a Minecraft keyword to the egg metadata or set custom keywords with `PLAYER_HEATMAP_LL_MINECRAFT_KEYWORDS`.

### Discord Popup Keeps Showing

The Discord popup is dismissed per browser using `localStorage`. If it shows again, the browser storage was cleared or a different browser/device is being used.

### Joins Show But Online Count Looks Wrong

The online count is estimated from the latest join/leave event for each player. If the server crashed or logs are incomplete, some sessions may remain open until the next leave line is seen.

### Duplicate Events

The plugin stores a persistent hash of processed log lines in the database. This prevents most duplicates even after cache clears or panel restarts.

## Branding

Plugin name: Player-Heatmap-LL

Author: Latitude Labs

Contact: pelicanplugins@latitudehost.uk

Website: https://latitudehost.uk

Discord: https://vltgg.net/discord

Footer: Made by latitudehost.uk

## Support

For plugin support, feature requests, or custom Pelican plugin work, contact:

```text
pelicanplugins@latitudehost.uk
```
