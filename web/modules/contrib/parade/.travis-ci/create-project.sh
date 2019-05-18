#!/usr/bin/env bash

set -e

# Make sure we are in the right folder.
cd ${TRAVIS_BUILD_DIR}
# Install a drupal-composer project.
composer create-project drupal-composer/drupal-project:8.x-dev ${TEST_SITE_DIR} --stability dev --no-interaction
cd ${TEST_SITE_DIR}
# Copy the scripts and docker-compose.yml
cp ../.travis-ci/* .
# Ensure simpletest folder.
sudo mkdir -m 777 -p web/sites/simpletest/browser_output
# Fix permissions.
sudo chown 1000:82 . -R
