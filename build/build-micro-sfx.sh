#!/usr/bin/env bash

set -euo pipefail

if [ ! -f spc ]; then
  curl -fsSL -o spc.tgz https://dl.static-php.dev/static-php-cli/spc-bin/nightly/spc-linux-x86_64.tar.gz
  tar -zxvf spc.tgz
  rm spc.tgz
  chmod +x spc
fi

EXTENSIONS=pcntl,ctype,zip,xml,exif,iconv,mbstring,phar,sockets,zlib,tokenizer,filter,openssl,curl

./spc doctor --auto-fix
./spc install-pkg upx

./spc download --with-php=8.3 --for-extensions $EXTENSIONS

./spc build --with-micro-fake-cli --with-upx-pack --build-micro $EXTENSIONS

echo "Build completed successfully!"
echo " file: buildroot/bin/micro.sfx"
echo " size: $(du -h buildroot/bin/micro.sfx | cut -f1)"
