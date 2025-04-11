#!/usr/bin/env bash

set -euo pipefail

wget https://dl.static-php.dev/static-php-cli/common/php-8.3.9-micro-linux-x86_64.tar.gz
tar -zxvf php-8.3.9-micro-linux-x86_64.tar.gz
rm php-8.3.9-micro-linux-x86_64.tar.g*
cat micro.sfx ../rah.phar > ../public/.rah/rah
chmod +x ../public/.rah/rah

echo " file: rah"
echo " size: $(du -h ../public/.rah/rah | cut -f1)"
echo "../public/.rah/rah -h"
