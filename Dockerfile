FROM php:8.2-apache

# Instalar dependencias del sistema y extensiones necesarias
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    unzip \
    git \
 && docker-php-ext-install pdo pdo_pgsql \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

# Configurar Apache: permitir .htaccess si existen
RUN sed -ri 's!/var/www/html!/var/www/html!g' /etc/apache2/sites-available/000-default.conf \
 && sed -ri 's/AllowOverride None/AllowOverride All/i' /etc/apache2/apache2.conf

# Copiar el código
COPY . /var/www/html

# Establecer permisos básicos
RUN chown -R www-data:www-data /var/www/html

# Variables de entorno por defecto (se pueden sobreescribir en compose)
ENV APP_ENV=production \
    DB_HOST=db \
    DB_PORT=5432 \
    DB_NAME=districarnes_navarro \
    DB_USER=postgres \
    DB_PASSWORD=postgres \
    GOOGLE_CLIENT_ID="" \
    PAYPAL_CLIENT_ID="" \
    PAYPAL_SECRET="" \
    PAYPAL_ENV=sandbox

EXPOSE 80

CMD ["apache2-foreground"]
