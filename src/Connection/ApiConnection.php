<?php

namespace VivereStage\LaravelApiFeeds\Connection;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Cache\Repository;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use VivereStage\LaravelApiFeeds\Exceptions\ApiClientException;

class ApiConnection
{
    protected Client $client;
    protected TokenStore $tokenStore;

    public function __construct()
    {
        $this->tokenStore = new TokenStore;
    }

    public function getClient()
    {
        if (empty($this->client)) {
            $this->createClient();
        }

        return $this->client;
    }

    protected function createClient()
    {
        if (! $this->tokenStore->hasToken()) {
            $this->authenticate();
        }
        $this->client = new Client([
            'base_uri' => config('vivere-api-feeds.url'),
            'headers' => [
                'Authorization' => 'Bearer ' . $this->tokenStore->getToken(),
                'Accept' => 'application/json',
            ],
            'verify' => config('vivere-api-feeds.verify_ssl', true),
        ]);
    }

    public function authenticate()
    {
        $authClient = new Client([
            'base_uri' => config('vivere-api-feeds.url'),
            'verify' => config('vivere-api-feeds.verify_ssl', true),
            'headers' => [
                'Accept' => 'application/json',
            ],
            'form_params' => [
                'app_id' => config('vivere-api-feeds.app_id'),
                'app_secret' => config('vivere-api-feeds.app_secret'),
            ]
        ]);
        try {
            $response = $authClient->post('authenticate');
            $result = (array) json_decode((string) $response->getBody(), true);
            if (!empty($result['token'])) {
                $this->tokenStore->setToken($result['token']);
            } else {
                $message = 'Failed to authorize.';
                $context = [
                    'response' => $result
                ];
                $this->getLogChannel()->error($message, $context);
                throw (new ApiClientException($message))
                    ->withContext($context);
            }
        } catch (GuzzleException $e) {
            $exception = ApiClientException::fromGuzzleException($e);
            $this->getLogChannel()->error($e->getMessage(), $exception->getContext());

            throw $exception;
        }
    }

    public function get(string $endpoint, array $query = [], bool $fresh = false)
    {
        $cacheTags = [
            config('vivere-api-feeds.url'),
            config('vivere-api-feeds.app_id'),
            config('vivere-api-feeds.app_secret'),
            $endpoint,
            $endpoint . '?' . http_build_query($query)
        ];

        /** @var Repository $repository */
        $key = 'content';
        if (Cache::supportsTags()) {
            $repository = Cache::tags($cacheTags);
        } else {
            $repository = Cache::store();
            $key = md5(json_encode($cacheTags)) . '.' . $key;
        }

        if ($fresh || ! config('vivere-api-feeds.cache_enabled', true)) {
            $repository->forget($key);
        }

        return $repository->remember(
            $key, config('vivere-api-feeds.cache_ttl', 60), function () use ($endpoint, $query) {
                return $this->getData($endpoint, $query);
            }
        );
    }

    protected function getData(string $endpoint, array $query = [], int $attempt = 1)
    {
        try {
            $response = $this->getClient()->get($endpoint, [
                'query' => $query,
            ]);
            return (array) json_decode((string) $response->getBody(), true);
        } catch (GuzzleException $e) {
            if ($e->getCode() == Response::HTTP_UNAUTHORIZED && $attempt < 2) {
                $this->authenticate();
                return $this->getData($endpoint, $query, $attempt + 1);
            } else {
                $exception = ApiClientException::fromGuzzleException($e);
                $this->getLogChannel()->error($e->getMessage(), $exception->getContext());

                throw $exception;
            }
        }
    }

    protected function getLogChannel()
    {
        return Log::channel(config('vivere-api-feeds.log_channel'));
    }
}
