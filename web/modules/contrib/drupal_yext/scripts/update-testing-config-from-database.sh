#!/bin/bash
#
# Developers can update the contents of ./docker-resources/config-for-testing
# with what is in the database.
#
set -e

docker-compose exec drupal /bin/bash -c 'drush cex --destination=/docker-resources/config-for-testing -y'
