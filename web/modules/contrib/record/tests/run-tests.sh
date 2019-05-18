#!/usr/bin/env bash

docker-compose exec web ./vendor/bin/grumphp run
docker-compose exec web ./vendor/bin/phpunit

# Waiting for https://github.com/openeuropa/drupal-module-template/issues/10
#docker-compose exec web ./vendor/bin/behat
