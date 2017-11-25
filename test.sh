#!/usr/bin/env bash

curl 127.0.0.1:8081

# async send to zipkin, so wait a bit before reading back
sleep 1

EXPECTED_SPANS=3
ACTUAL_SPANS=$(curl -s 127.0.0.1:9411/api/v2/traces | jq '.[0] | length')

if [ $ACTUAL_SPANS -eq $EXPECTED_SPANS ];
then
    echo "${EXPECTED_SPANS} spans expected, got ${ACTUAL_SPANS}"
    exit 0
else
    echo "${EXPECTED_SPANS} spans expected, got ${ACTUAL_SPANS}"
    exit 1
fi

