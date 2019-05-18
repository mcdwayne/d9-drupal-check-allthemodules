#!/usr/bin/env bash

# Ensure correct directory.
cd ${TRAVIS_BUILD_DIR}/${TEST_SITE_DIR}

# Start docker.
docker-compose up -d
# Execute install script.
docker-compose exec --user 1000 php sh site-install.sh
