<?php

use GuzzleHttp\Client;
use Zipkin\Endpoint;
use Zipkin\Samplers\BinarySampler;
use Zipkin\TracingBuilder;

function create_tracing($endpointName, $ipv4)
{
    $endpoint = Endpoint::create($endpointName, $ipv4, null, 2555);
    $httpClient = new Client();

    $logger = new \Monolog\Logger('log');
    $logger->pushHandler(new \Monolog\Handler\ErrorLogHandler());

    $reporter = new Zipkin\Reporters\HttpLogging($httpClient, $logger);
    $sampler = BinarySampler::createAsAlwaysSample();
    $tracing = TracingBuilder::create()
        ->havingLocalEndpoint($endpoint)
        ->havingSampler($sampler)
        ->havingReporter($reporter)
        ->build();
    return $tracing;
}

