until nc -z -v -w30 naturallydb 3306
do
  echo "Waiting for database connection..."
  sleep 10
done

if [ $APP_ENV = "local" ];
then

  CONTAINER_ALREADY_STARTED="CONTAINER_ALREADY_STARTED_PLACEHOLDER"
  if [ ! -e ./storage/$CONTAINER_ALREADY_STARTED ]; then
    touch ./storage/$CONTAINER_ALREADY_STARTED
    echo "** First container startup **"
    composer install
    php artisan migrate
    echo "APP_KEY=" >> .env
    php artisan key:generate
    php artisan passport:install
    chown 1000.1000 storage/*
    chmod 775 /var/www/storage/logs
    php artisan storage:link
  fi
fi

php artisan config:cache
php artisan cache:clear
php artisan optimize:clear
php artisan route:cache