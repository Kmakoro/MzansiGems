FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends libcurl4-openssl-dev \
    && docker-php-ext-install pdo_mysql mysqli curl \
    && docker-php-ext-enable pdo_mysql mysqli \
    && a2enmod rewrite headers \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/
RUN mkdir -p /var/www/html/uploads/gems \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80
