#!/usr/bin/env bash

set -euo pipefail

./bin/console startup:ensure-api-key-created -vvv
