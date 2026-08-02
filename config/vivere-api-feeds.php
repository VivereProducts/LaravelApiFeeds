<?php

return [
    'namespace' => \App\Support\VivereStage\ApiFeeds::class,
    'url' => env('VIVERE_API_FEEDS_URL', 'https://api.viverestage.com/api/feeds'),
    'app_id' => env('VIVERE_API_FEEDS_APP_ID'),
    'app_secret' => env('VIVERE_API_FEEDS_APP_SECRET'),
    'token_store' => 'redis',
    'token_ttl' => 18000,
    'log_channel' => 'daily',
    'verify_ssl' => env('VIVERE_API_FEEDS_SSL', true),
];
