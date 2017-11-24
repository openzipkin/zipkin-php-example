#!/usr/bin/env bash

curl localhost:8081

#sleep 3

EXPECTED_SPANS=3
ACTUAL_SPANS=$(curl -s http://localhost:9411/zipkin/api/v1/traces | jq '.[0] | length')

if [ $ACTUAL_SPANS -eq $EXPECTED_SPANS ];
then
    exit 0
else
    echo "${EXPECTED_SPANS} spans expected, got ${ACTUAL_SPANS}"
    exit 1
fi

