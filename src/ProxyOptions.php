<?php

namespace Balerka\LaravelProxy;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ProxyOptions
{
    /**
     * @return array{handler: HandlerStack, proxy: string, connect_timeout: float}
     */
    public static function make(): array
    {
        $stack = HandlerStack::create();
        $stack->push(self::middleware());

        return [
            'handler' => $stack,
            'proxy' => config('proxy.list', [])[0] ?? '',
            'connect_timeout' => (float) config('proxy.connect_timeout', 5),
        ];
    }

    public static function middleware(): callable
    {
        $proxies = config('proxy.list', []);

        return static function (callable $handler) use ($proxies): callable {
            $retry = Middleware::retry(
                static function (int $retries, RequestInterface $request, ?ResponseInterface $response = null, ?Throwable $exception = null) use ($proxies): bool {
                    if ($retries >= count($proxies) - 1) {
                        return false;
                    }

                    if ($response?->getStatusCode() === 407) {
                        return true;
                    }

                    if (! $exception instanceof ConnectException && ! $exception instanceof RequestException) {
                        return false;
                    }

                    $context = $exception->getHandlerContext();

                    if (($context['errno'] ?? null) === 28 && ($context['pretransfer_time'] ?? 0) > 0
                        && ! in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
                        return false;
                    }

                    return $exception instanceof ConnectException || ($context['http_connectcode'] ?? 0) >= 400;
                },
                static fn (): int => 0,
            );

            return $retry(static function (RequestInterface $request, array $options) use ($handler, $proxies): PromiseInterface {
                $options['proxy'] = $proxies[$options['retries'] ?? 0] ?? '';

                if (($options['retries'] ?? 0) > 0 && $request->getBody()->isSeekable()) {
                    $request->getBody()->rewind();
                }

                return $handler($request, $options);
            });
        };
    }
}
