#!/bin/bash
docker exec -it module_testing bash -c "vendor/drush/drush/drush en -y simpletest"

docker exec -it module_testing bash -c "test -d /var/www/html/sites/simpletest || mkdir /var/www/html/sites/simpletest && chmod 777 /var/www/html/sites/simpletest"
docker exec -it module_testing bash -c "test -d /var/www/html/sites/default/files/simpletest || mkdir -p /var/www/html/sites/default/files/simpletest && chmod -R 777 /var/www/html/sites/default/files"
docker exec -it module_testing bash -c "chown -R www-data /var/www/html"
docker exec -it module_testing bash -c "sudo -u www-data php core/scripts/run-tests.sh --browser --url http://drupal/ --class \"\Drupal\Tests\formatter_suggest\Functional\FormatterSuggestTest\""