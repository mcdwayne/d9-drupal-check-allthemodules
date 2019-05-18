#!/usr/bin/env bash


# Ensure correct directory.
cd ${TRAVIS_BUILD_DIR}/${TEST_SITE_DIR}

# 82 is the web server user. We use that instead of 1000 to fix permission errors.
# See: https://www.drupal.org/project/drupal/issues/2867042
docker-compose exec --user 82 php sh -c "vendor/bin/phpunit --verbose --stop-on-failure --debug -c phpunit.xml"
