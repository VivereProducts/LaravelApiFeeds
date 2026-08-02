<?php

namespace VivereStage\LaravelApiFeeds\Exceptions;

use Exception;
use GuzzleHttp\Exception\GuzzleException;

class ApiClientException extends Exception
{
    protected array $context = [];

    public static function fromGuzzleException(GuzzleException $e): self
    {
        $exception = new self(
            $e->getMessage(),
            $e->getCode(),
            $e
        );

        $context = [
            'exception' => $e,
        ];
        if (
            method_exists($e, 'hasResponse') &&
            method_exists($e, 'getResponse') &&
            $e->hasResponse()
        ) {
            $context['response'] = json_decode((string) $e->getResponse()->getBody(), true)
                ?? (string) $e->getResponse()->getBody();
        }

        $exception->withContext($context);

        return $exception;
    }

    public function withContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
