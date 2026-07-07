<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Widget Position
    |--------------------------------------------------------------------------
    |
    | Where to place the heatmap widget on the server console page.
    | Options: top, above_console, below_console, bottom
    |
    */
    'widget_position' => env('PLAYER_HEATMAP_LL_WIDGET_POSITION', 'below_console'),

    /*
    |--------------------------------------------------------------------------
    | Show Peak Indicator
    |--------------------------------------------------------------------------
    |
    | Whether to highlight the peak time slot with a blue border.
    |
    */
    'show_peak_indicator' => env('PLAYER_HEATMAP_LL_SHOW_PEAK_INDICATOR', true),

    /*
    |--------------------------------------------------------------------------
    | Show Stats Header
    |--------------------------------------------------------------------------
    |
    | Whether to show the peak/average stats in the widget header.
    |
    */
    'show_stats_header' => env('PLAYER_HEATMAP_LL_SHOW_STATS_HEADER', true),

    /*
    |--------------------------------------------------------------------------
    | Sample Alpha (EMA weight)
    |--------------------------------------------------------------------------
    |
    | Weight for new samples in the exponential moving average.
    | Higher = more responsive to recent changes. Range: 0.0 - 1.0
    |
    */
    'sample_alpha' => env('PLAYER_HEATMAP_LL_SAMPLE_ALPHA', 0.3),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (days)
    |--------------------------------------------------------------------------
    |
    | How long to keep heatmap data before it expires from cache.
    | Actual data is in the database; this is for fallback caching.
    |
    */
    'cache_ttl_days' => env('PLAYER_HEATMAP_LL_CACHE_TTL', 30),

    'minecraft_keywords' => array_filter(array_map('trim', explode(',', env(
        'PLAYER_HEATMAP_LL_MINECRAFT_KEYWORDS',
        'minecraft,paper,spigot,purpur,forge,fabric,vanilla,bukkit,neoforge'
    )))),

    'log_paths' => array_filter(array_map('trim', explode(',', env(
        'PLAYER_HEATMAP_LL_LOG_PATHS',
        'logs/latest.log,server.log,console.log,logs/debug.log'
    )))),

    'brand_name' => 'Latitude Labs',
    'brand_email' => 'pelicanplugins@latitudehost.uk',
    'brand_footer' => 'Made by latitudehost.uk',
    'discord_url' => 'https://vltgg.net/discord',
];
