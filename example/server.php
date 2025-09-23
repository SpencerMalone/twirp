<?php

require __DIR__ . '/../lib/vendor/autoload.php';

$request = \GuzzleHttp\Psr7\ServerRequest::fromGlobals();

$handler = new \Twitch\Twirp\Example\HaberdasherServer(new \Twirp\Example\Haberdasher());

// To include default values in JSON responses, you can either:
// 1. Set emitJsonDefaults=true in the constructor:
// $handler = new \Twitch\Twirp\Example\HaberdasherServer(
//     new \Twirp\Example\Haberdasher(),
//     null, // ServerHooks
//     null, // ResponseFactoryInterface
//     null, // StreamFactoryInterface
//     '/twirp', // prefix
//     true // emitJsonDefaults
// );
//
// 2. Or use the setter method after creation:
// $handler->setEmitJsonDefaults(true);

$response = $handler->handle($request);

if (!headers_sent()) {
    // status
    header(sprintf('HTTP/%s %s %s', $response->getProtocolVersion(), $response->getStatusCode(), $response->getReasonPhrase()), true, $response->getStatusCode());

    // headers
    foreach ($response->getHeaders() as $header => $values) {
        foreach ($values as $value) {
            header($header . ': ' . $value, false, $response->getStatusCode());
        }
    }
}

echo $response->getBody();
