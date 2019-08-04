<?php

use Zipkin\Endpoint;
use Zipkin\Samplers\BinarySampler;
use Zipkin\TracingBuilder;

function create_tracing($endpointName, $ipv4)
{
    $httpReporterURL = getenv('HTTP_REPORTER_URL');
    if ($httpReporterURL === false) {
        $httpReporterURL = 'http://localhost:9411/api/v2/spans';
    }

    $endpoint = Endpoint::create($endpointName, $ipv4, null, 2555);

    /* Do not copy this logger into production.
     * Read https://github.com/Seldaek/monolog/blob/master/doc/01-usage.md#log-levels
     */
    $logger = new \Monolog\Logger('log');
    $logger->pushHandler(new \Monolog\Handler\ErrorLogHandler());

    $reporter = new Zipkin\Reporters\Http(
        \Zipkin\Reporters\Http\CurlFactory::create(),
        ['endpoint_url' => $httpReporterURL]
    );
    $sampler = BinarySampler::createAsAlwaysSample();
    $tracing = TracingBuilder::create()
        ->havingLocalEndpoint($endpoint)
        ->havingSampler($sampler)
        ->havingReporter($reporter)
        ->build();
    return $tracing;
}
