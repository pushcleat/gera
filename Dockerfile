FROM drupal:10.1.6-php8.2-fpm-bullseye

COPY . /opt/drupal/

RUN ls -l /opt/drupal/web/sites/default/
RUN chown -R www-data:www-data /opt/drupal/web/sites /opt/drupal/web/modules /opt/drupal/web/themes;
RUN chmod 777 /opt/drupal/web/sites/default/settings.php

