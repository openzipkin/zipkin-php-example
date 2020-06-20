<?php

use Zipkin\Propagation\Map;
use Zipkin\Propagation\TraceContext;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/functions.php';

$tracing = create_tracing('backend', '127.0.0.2');

$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

usleep(1000 * mt_rand(1, 3));

$carrier = array_map(function ($header) {
    return $header[0];
}, $request->headers->all());

/* Extracts the context from the HTTP headers */
$extractor = $tracing->getPropagation()->getExtractor(new Map());
$extractedContext = $extractor($carrier);

/* Get users from DB */
$tracer = $tracing->getTracer();

$span = $extractedContext instanceof TraceContext
    ? $tracer->joinSpan($extractedContext)
    : $tracer->nextSpan($extractedContext);

$span->start();
$span->setKind(Zipkin\Kind\SERVER);
$span->setName('parse_request');
usleep(1000 * mt_rand(1, 3));

$childSpan = $tracer->newChild($span->getContext());
$childSpan->start();
$childSpan->setKind(Zipkin\Kind\CLIENT);
$childSpan->setName('user:get_list:mysql_query');
$childSpan->tag("sql.query", "SELECT * FROM users LIMIT 10");

usleep(30000);

$childSpan->finish();

usleep(1000 * mt_rand(1, 3));

$span->finish();

/* Sends the trace to zipkin once the response is served */
register_shutdown_function(function () use ($tracer) {
    $tracer->flush();
});
