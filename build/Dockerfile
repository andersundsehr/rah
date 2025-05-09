# syntax=docker/dockerfile:1-labs
FROM pluswerk/php-dev:nginx-8.3-alpine AS build-micro-sfx

COPY ./build/build-micro-sfx.sh /app/build/build-micro-sfx.sh

RUN cd /app/build && ./build-micro-sfx.sh

FROM pluswerk/php-dev:nginx-8.3-alpine AS build

ENV WEB_DOCUMENT_ROOT=/app/public \
    RAH_STORAGE_PATH=/storage \
    RAH_MAX_DISK_USAGE=10G

WORKDIR /app

RUN git config --global --add safe.directory /app

RUN COMPOSER_BIN_DIR=/root/.composer/vendor/bin composer global require humbug/box --ansi

COPY composer.json composer.lock /app/

RUN composer install --no-scripts --no-plugins

COPY --exclude=build.sh --exclude=build/Dockerfile . /app/

RUN composer install --ansi

RUN ./vendor/bin/grumphp run --ansi

RUN composer test --ansi

RUN composer install --no-dev --ansi

RUN /root/.composer/vendor/bin/box compile --ansi

COPY --from=build-micro-sfx /app/build/buildroot/bin/micro.sfx /app/build/buildroot/bin/micro.sfx

RUN cd /app/build && ./package-rah-bin.sh && /app/public/.rah/rah --version --ansi

RUN mkdir -p /output && \
    mv /app/bin /output/bin && \
    mv /app/src /output/src && \
    mv /app/vendor /output/vendor && \
    mv /app/public /output/public && \
    mv /app/templates /output/templates && \
    mv /app/config /output/config && \
    mv /app/.env.dev /app/composer.json /app/composer.lock /app/install.sh /output/ && \
    echo "APP_ENV=prod" > /output/.env && \
    echo "APP_DEBUG=0" >> /output/.env

FROM webdevops/php-nginx:8.3-alpine AS production


ARG LAST_GIT_TAG
ENV WEB_DOCUMENT_ROOT=/app/public \
    RAH_STORAGE_PATH=/storage \
    RAH_MAX_DISK_USAGE=10G \
    RAH_VERSION=$LAST_GIT_TAG

VOLUME /storage

COPY --from=build --chown=1000:1000 /output/ /var/www/html/
COPY --from=build --chown=1000:1000 /output/bin/startup.sh /opt/docker/provision/entrypoint.d/99-startup.sh
