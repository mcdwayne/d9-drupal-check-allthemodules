#!/usr/bin/env bash

# Edit composer config to not symlink the repo
cd ../orca-build
composer config repositories.drupal/acquia_contenthub '{"type": "path", "url": "../../content-hub-d8", "options": { "symlink": false }}'
# Must rebuild the lock file to deploy it.
rm -rf composer.lock
# Run the BLT installer
vendor/bin/blt artifact:deploy --commit-msg "Automated commit by Travis CI for Build ${TRAVIS_BUILD_ID}" --branch "${DRUPAL}-${TRAVIS_BRANCH}" --ignore-dirty --no-interaction --verbose -Dgit.remotes.1='contenthubqa@svn-29892.prod.hosting.acquia.com:contenthubqa.git'
