#!/usr/bin/env bash
echo "# Starting services"
cd web
php -S localhost:8888 > /dev/null 2>&1 &
chromedriver > /dev/null 2>&1 &
export SIMPLETEST_BASE_URL=http://localhost:8888
service mysql start
mysql -u $MYSQL_USER -p$MYSQL_PASSWORD -e "CREATE DATABASE $MYSQL_DATABASE"
export SIMPLETEST_DB=mysql://$MYSQL_USER:$MYSQL_PASSWORD@127.0.0.1:3306/$MYSQL_DATABASE
export MINK_DRIVER_ARGS_WEBDRIVER='["chrome", {"browserName":"chrome","chromeOptions":{"args":["--disable-gpu","--headless","--disable-dev-shm-usage","--no-sandbox","--lang=en-GB"]}}, "http://localhost:9515"]'

echo "# Running tests"
../vendor/bin/phpunit -c $CI_PROJECT_DIR/phpunit.xml.dist --bootstrap core/tests/bootstrap.php
