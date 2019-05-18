#!/bin/sh
echo "# Calculating Drupal $DRUPAL_VERSION_TYPE version"
DRUPAL_VERSION_MINOR=$(composer show drupal/core -la | grep "latest +: ([0-9]+\.[0-9]+)" -Eo | grep "[0-9]+$" -Eo)

if [ "$DRUPAL_VERSION_TYPE" = "supported" ]; then
  DRUPAL_VERSION="~8.$DRUPAL_VERSION_MINOR.0"
elif [ "$DRUPAL_VERSION_TYPE" = "security" ]; then
  DRUPAL_VERSION_MINOR=$((DRUPAL_VERSION_MINOR - 1))
  DRUPAL_VERSION="~8.$DRUPAL_VERSION_MINOR.0"
elif [ "$DRUPAL_VERSION_TYPE" = 'supported-dev' ]; then
  DRUPAL_VERSION="8.$DRUPAL_VERSION_MINOR.x-dev"
elif [ "$DRUPAL_VERSION_TYPE" = 'dev' ]; then
  DRUPAL_VERSION_MINOR=$((DRUPAL_VERSION_MINOR + 1))
  DRUPAL_VERSION="8.$DRUPAL_VERSION_MINOR.x-dev"
else
  echo "Error: Unknown Drupal minor version type."
  exit 1
fi

echo "# Preparing GIT repo"

# Remove the .git directory from our repo so we can treat it as a path.
cd $CI_PROJECT_DIR
rm .git -rf

# Create our main Drupal project.
echo "# Creating Drupal $DRUPAL_VERSION project"
composer create-project drupal-composer/drupal-project:8.x-dev $DRUPAL_BUILD_ROOT --stability dev --no-interaction --no-install
cd $DRUPAL_BUILD_ROOT

# Set our drupal core version.
composer require drupal/core:$DRUPAL_VERSION --no-update
composer require webflo/drupal-core-require-dev:$DRUPAL_VERSION --no-update --dev

# Add our CI repository, as well as re-adding the Drupal package repo.
echo "# Configuring package repos"
composer config repositories.0 path $CI_PROJECT_DIR
composer config repositories.1 composer https://packages.drupal.org/8
composer config extra.enable-patching true
composer config discard-changes true

# Prepare dev dependencies.
composer require wikimedia/composer-merge-plugin --no-update
composer config extra.merge-plugin.include "$CI_PROJECT_DIR/composer.json"
composer config extra.merge-plugin.merge-dev true

# Now require contacts which will pull itself from the paths.
echo "# Requiring Contacts"
composer require drupal/contacts:dev-master
