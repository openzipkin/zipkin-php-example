<?php

use GuzzleHttp\Client;
use Zipkin\Annotation;
use Zipkin\Endpoint;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Timestamp;
use Zipkin\TracingBuilder;

require_once __DIR__ . '/vendor/autoload.php';

$endpoint = Endpoint::create('backend', '127.0.0.3', null, 2555);
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

$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

$carrier = array_map(function ($header) {
    return $header[0];
}, $request->headers->all());

$extractor = $tracing->getPropagation()->getExtractor(new \Zipkin\Propagation\Map());
$traceContext = $extractor($carrier);

$tracer = $tracing->getTracer();
$span = $tracer->newChild($traceContext);
$span->start();
$span->setName('user:get_list:mysql_query');
$span->annotate(Annotation::SERVER_RECEIVE, Timestamp\now());

usleep(100);

$span->annotate(Annotation::SERVER_SEND, Timestamp\now());

$span->finish(Timestamp\now());

$tracer->flush();
