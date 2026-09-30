<?php

$proxyValue = trim((string) env('HTTP_PROXY', ''));
$proxies = str_starts_with($proxyValue, '[')
    ? json_decode($proxyValue, true, 512, JSON_THROW_ON_ERROR)
    : [$proxyValue];
$proxies = array_values(array_unique(array_map(
    static fn (string $proxy): string => str_contains($proxy, '://') ? $proxy : 'http://'.$proxy,
    array_filter(array_map('trim', $proxies)),
)));

return [
    'list' => $proxies,
    'connect_timeout' => 5,
];
