#FROM webdevops/php-nginx:8.3-alpine
FROM pluswerk/php-dev:nginx-8.3-alpine

COPY . /app/
WORKDIR /app
ENV WEB_DOCUMENT_ROOT=/app/public
