FROM drupal:10.1.6-php8.2-fpm-bullseye

USER www-data

COPY ./ /opt/drupal/
