FROM php:8.3-cli
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /package
COPY . .
RUN composer install --no-interaction --prefer-dist
CMD ["composer", "check"]
