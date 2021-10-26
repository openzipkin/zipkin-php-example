<?php

use Zipkin\TracingBuilder;
use Zipkin\Samplers\BinarySampler;
use Zipkin\Endpoint;

/**
 * create_tracing function is a handy function that allows you to create a tracing
 * component by just passing the local service information. If you need to pass a
 * custom zipkin server URL use the HTTP_REPORTER_URL env var.
 */
function create_tracing($localServiceName, $localServiceIPv4, $localServicePort = null)
{
    $httpReporterURL = getenv('HTTP_REPORTER_URL');
    if ($httpReporterURL === false) {
        $httpReporterURL = 'http://localhost:9411/api/v2/spans';
    }

    $endpoint = Endpoint::create($localServiceName, $localServiceIPv4, null, $localServicePort);

    /* Do not copy this logger into production.
     * Read https://github.com/Seldaek/monolog/blob/master/doc/01-usage.md#log-levels
     */
    $logger = new \Monolog\Logger('log');
    $logger->pushHandler(new \Monolog\Handler\ErrorLogHandler());

    $reporter = new Zipkin\Reporters\Http(['endpoint_url' => $httpReporterURL]);
    $sampler = BinarySampler::createAsAlwaysSample();
    $tracing = TracingBuilder::create()
        ->havingLocalEndpoint($endpoint)
        ->havingSampler($sampler)
        ->havingReporter($reporter)
        ->build();
    return $tracing;
}
