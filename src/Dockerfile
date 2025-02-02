FROM php:8.2-apache

# Instalar extensões e dependências
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install gd zip pdo pdo_pgsql pgsql \
    && a2enmod rewrite 

RUN pecl install opentelemetry

# Configurar o DocumentRoot (opcional)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html/

# Permissão de escrita (opcional)
RUN chown -R www-data:www-data .

# Copia o composer 
COPY ./src/composer.json .
 
# Instalar as dependências do projeto
RUN composer install
COPY ./src .

# Expor a porta 80
EXPOSE 80

# Comando de inicialização
CMD ["apache2-foreground"]
