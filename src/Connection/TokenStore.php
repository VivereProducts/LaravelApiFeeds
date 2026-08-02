<?php

namespace VivereStage\LaravelApiFeeds\Connection;

use Carbon\CarbonInterval;
use Illuminate\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class TokenStore
{
    protected ?string $token = null;
    protected Repository $store;
    protected string $cacheKey = 'viverestage_api_feeds_token';

    public function __construct()
    {
        $this->store = Cache::store(
            config('vivere-api-feeds.token_store', 'redis')
        );
    }

    public function getToken(): ?string
    {
        if (empty($this->token)) {
            $this->token = $this->store->get($this->cacheKey);
        }

        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;

        $this->store->set(
            $this->cacheKey,
            $token,
            new CarbonInterval(
                seconds: config('vivere-api-feeds.token_ttl', 18000)
            )
        );
    }

    public function hasToken(): bool
    {
        $token = $this->getToken();
        return !empty($token);
    }
}
