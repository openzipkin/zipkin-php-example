<?php

use GuzzleHttp\Client;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Timestamp;
use Zipkin\Instrumentation\Http\Client\Client as ZipkinClient;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/functions.php';

$tracing = create_tracing('frontend', '127.0.0.1');

$tracer = $tracing->getTracer();

/* Always sample traces */
$defaultSamplingFlags = DefaultSamplingFlags::createAsSampled();

/* Creates the main span */
$span = $tracer->newTrace($defaultSamplingFlags);
$span->start(Timestamp\now());
$span->setName('parse_request');
$span->setKind(Zipkin\Kind\SERVER);

// We need to open a scope so the http client can retrieve the current
// context from it.
$scopeCloser = $tracer->openScope($span);

usleep(100 * mt_rand(1, 3));

$httpClient = new ZipkinClient(new Client, $tracing);
$request = new \GuzzleHttp\Psr7\Request('POST', 'localhost:9000');
$response = $httpClient->sendRequest($request);

echo $response->getBody();

usleep(100 * mt_rand(1, 3));

$span->finish();
$scopeCloser();

/* Sends the trace to zipkin once the response is served */

register_shutdown_function(function () use ($tracer) {
    $tracer->flush();
});
