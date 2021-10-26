<?php

use Zipkin\Timestamp;
use Zipkin\Propagation\Map;
use Zipkin\Propagation\DefaultSamplingFlags;
use GuzzleHttp\Client;

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

usleep(100 * mt_rand(1, 3));

/* Creates the span for getting the users list */
$childSpan = $tracer->newChild($span->getContext());
$childSpan->start();
$childSpan->setKind(Zipkin\Kind\CLIENT);
$childSpan->setName('users:get_list');

$headers = [];

/* Injects the context into the wire */
$injector = $tracing->getPropagation()->getInjector(new Map());
$injector($childSpan->getContext(), $headers);

/* HTTP Request to the backend */
$httpClient = new Client();
$request = new \GuzzleHttp\Psr7\Request('POST', 'localhost:9000', $headers);
$childSpan->annotate('request_started', Timestamp\now());
$response = $httpClient->send($request);
$childSpan->annotate('request_finished', Timestamp\now());

$childSpan->finish();

$span->finish();

/* Sends the trace to zipkin once the response is served */

register_shutdown_function(function () use ($tracer) {
    $tracer->flush();
});
