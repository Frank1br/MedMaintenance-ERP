FROM php:8.2-fpm

# Instalar dependências do sistema e extensões PHP requeridas pelo Adianti
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd zip pdo pdo_pgsql pgsql mbstring xml

# Configurações personalizadas do PHP (opcional, mas recomendado)
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini
RUN echo "upload_max_filesize=50M" > /usr/local/etc/php/conf.d/uploads.ini
RUN echo "post_max_size=50M" > /usr/local/etc/php/conf.d/post.ini

WORKDIR /var/www/html