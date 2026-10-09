#!/usr/bin/env bash

set -euo pipefail

PHP_MICRO_VERSION=${PHP_MICRO_VERSION:-8.5.11}
archive="php-${PHP_MICRO_VERSION}-micro-linux-x86_64.tar.gz"
wget "https://dl.static-php.dev/static-php-cli/common/${archive}"
tar -zxvf "$archive"
rm "$archive"
cat micro.sfx ../rah.phar > ../public/.rah/rah
chmod +x ../public/.rah/rah

echo " file: rah"
echo " size: $(du -h ../public/.rah/rah | cut -f1)"
echo "../public/.rah/rah -h"
