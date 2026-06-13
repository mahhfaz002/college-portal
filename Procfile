release: php artisan optimize:clear && php artisan migrate --force && php artisan storage:link && php artisan config:cache && php artisan route:cache && php artisan view:cache
web: vendor/bin/heroku-php-apache2 public/
