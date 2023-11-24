FROM drupal:10.1.6-php8.2-fpm-bullseye

COPY . /opt/drupal/

RUN chown -R www-data:www-data /opt/drupal/web/sites /opt/drupal/web/modules /opt/drupal/web/themes; \
    rmdir /var/www/html; \
    mkdir /var/www/html/web; \
    ln -sf /opt/drupal/web /var/www/html/web;

