#!/usr/bin/env bash

set -euo pipefail
set -x

LAST_GIT_TAG=$(git tag --points-at HEAD)

docker build \
 -t andersundsehr/rah:latest \
 --build-arg LAST_GIT_TAG=$LAST_GIT_TAG \
 -f build/Dockerfile \
 .
