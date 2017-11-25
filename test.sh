#!/usr/bin/env bash

/sbin/ip route|awk '/default/ { print $3 }'

curl 0.0.0.0:8081

sleep 1

EXPECTED_SPANS=3
ACTUAL_SPANS=$(curl -s http://localhost:9411/zipkin/api/v1/traces | jq '.[0] | length')

if [ $ACTUAL_SPANS -eq $EXPECTED_SPANS ];
then
    echo "${EXPECTED_SPANS} spans expected, got ${ACTUAL_SPANS}"
    exit 0
else
    echo "${EXPECTED_SPANS} spans expected, got ${ACTUAL_SPANS}"
    exit 1
fi

