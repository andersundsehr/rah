#!/usr/bin/env bash

set -euo pipefail

docker build \
 -t andersundsehr/rah:latest \
 --build-arg LAST_GIT_TAG=$(git tag --points-at HEAD) \
 -f build/Dockerfile \
 .
