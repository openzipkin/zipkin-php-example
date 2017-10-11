<?php

use GuzzleHttp\Client;
use Zipkin\Annotation;
use Zipkin\Endpoint;
use Zipkin\Propagation\DefaultSamplingFlags;
use Zipkin\Propagation\Map;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Timestamp;
use Zipkin\TracingBuilder;

require_once __DIR__ . '/vendor/autoload.php';

$endpoint = Endpoint::create('frontend', '127.0.0.2', null, 2555);
$client = new Client();

$logger = new \Monolog\Logger('log');
$logger->pushHandler(new \Monolog\Handler\ErrorLogHandler());

$reporter = new Zipkin\Reporters\HttpLogging($client, $logger);
$sampler = BinarySampler::createAsAlwaysSample();
$tracing = TracingBuilder::create()
    ->havingLocalEndpoint($endpoint)
    ->havingSampler($sampler)
    ->havingReporter($reporter)
    ->build();

$tracer = $tracing->getTracer();

$defaultSamplingFlags = DefaultSamplingFlags::createAsSampled();
$span = $tracer->newTrace($defaultSamplingFlags);
$span->start(Timestamp\now());
$span->setName('http_request');
$span->annotate(Annotation::SERVER_RECEIVE, Timestamp\now());

usleep(100 * mt_rand(1, 3));

$childSpan = $tracer->newChild($span->getContext());
$childSpan->start();
$childSpan->setName('users:get_list');

$headers = new ArrayObject();

$injector = $tracing->getPropagation()->getInjector(new Map());
$injector($childSpan->getContext(), $headers);

$childSpan->annotate(Annotation::CLIENT_START, Timestamp\now());

$request = new \GuzzleHttp\Psr7\Request('POST', 'localhost:8002', (array) $headers);
$response = $client->send($request);

$childSpan->annotate(Annotation::CLIENT_RECEIVE, Timestamp\now());

$childSpan->finish(Timestamp\now());

$span->finish(Timestamp\now());

register_shutdown_function(function () use ($tracer) {
    $tracer->flush();
});
