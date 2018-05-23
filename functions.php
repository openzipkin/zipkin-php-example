<?php

use GuzzleHttp\Client;
use Zipkin\Endpoint;
use Zipkin\Samplers\BinarySampler;
use Zipkin\TracingBuilder;

function create_tracing($endpointName, $ipv4)
{
    $endpoint = Endpoint::create($endpointName, $ipv4, null, 2555);

    /* Do not copy this logger into production.
     * Read https://github.com/Seldaek/monolog/blob/master/doc/01-usage.md#log-levels
     */
    $logger = new \Monolog\Logger('log');
    $logger->pushHandler(new \Monolog\Handler\ErrorLogHandler());

    $reporter = new Zipkin\Reporters\Http(\Zipkin\Reporters\Http\CurlFactory::create());
    $sampler = BinarySampler::createAsAlwaysSample();
    $tracing = TracingBuilder::create()
        ->havingLocalEndpoint($endpoint)
        ->havingSampler($sampler)
        ->havingReporter($reporter)
        ->build();
    return $tracing;
}
