FROM php:8.4-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev

RUN docker-php-ext-install pdo pdo_pgsql

COPY . .

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer

RUN composer install --no-dev --optimize-autoloader

CMD php artisan serve --host=0.0.0.0 --port=$PORT