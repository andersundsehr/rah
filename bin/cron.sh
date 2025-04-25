#!/usr/bin/env bash

set -euo pipefail

./bin/console background:cleanup-old-deployments -vvv --ansi
