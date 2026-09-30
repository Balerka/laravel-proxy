<?php

namespace Balerka\LaravelProxy\Telegram;

use Balerka\LaravelProxy\ProxyOptions;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Telegram\Bot\HttpClients\GuzzleHttpClient;

class ProxyHttpClient extends GuzzleHttpClient
{
    public function send(
        string $url,
        string $method,
        array $headers = [],
        array $options = [],
        bool $isAsyncRequest = false
    ): ResponseInterface|PromiseInterface|null {
        if (! array_key_exists(RequestOptions::PROXY, $options)) {
            $options = array_replace(ProxyOptions::make(), $options);
        }

        return parent::send($url, $method, $headers, $options, $isAsyncRequest);
    }
}
