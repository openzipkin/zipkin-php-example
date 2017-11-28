<?php

use Zipkin\Timestamp;
use Zipkin\Propagation\Map;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/functions.php';

$tracing = create_tracing('backend', '127.0.0.2');

$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

$carrier = array_map(function ($header) {
    return $header[0];
}, $request->headers->all());

/* Extracts the context from the HTTP headers */
$extractor = $tracing->getPropagation()->getExtractor(new Map());
$traceContext = $extractor($carrier);

/* Get users from DB */
$tracer = $tracing->getTracer();
$span = $tracer->newChild($traceContext);
$span->start();
$span->setName('user:get_list:mysql_query');

usleep(100);

$span->finish(Timestamp\now());

/* Sends the trace to zipkin once the response is served */
register_shutdown_function(function () use ($tracer) {
    $tracer->flush();
});
