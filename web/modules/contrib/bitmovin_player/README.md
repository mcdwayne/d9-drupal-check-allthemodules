## Installation with Drush
* Download the module, and composer manager: `drush dl bitdash_player,
  composer_manager, entity`
* Enable Composer Manager: `drush en composer_manager`
* Composer Manager writes a file to `sites/default/files/composer`
* Enable bitdash player: `drush en bitdash_player`
* As long as Composer Manager is enabled, the required dependencies will be
added to `sites/all/vendor` as soon as you enable the bitdash module
* If you don't see any dependencies download, try `drush composer-json-rebuild`
 followed by `drush composer-manager install` when in docroot
* Check `/admin/config/system/composer-manager` to ensure it's all green
* Tip: If you ever want to update your composer dependencies to a more recent
 version (while respecting versioning constraints) try `drush composer-manager
  update`
* Tip: See composer manager drupal documentation to understand how this all
 works

## Manual installation using composer
Bitdash Player requires the bitmovin/bitcodin-php library. You have to install
this library using composer.

* Download the [Composer Manager](http://drupal.org/project/composer_manager)
  module to the modules directory
* Download the [Entity API](http://drupal.org/project/entity) module to the
  modules directory
* Enable Bitdash Player at `/admin/modules`
* Navigate to the bitdash_player module-directory
  (by default `sites/all/modules/contrib`) using your command line tool.
* Run in the command line `composer install`. The required dependencies will be
added to `sites/all/vendor`.

## Configuration
* Navigate to `/admin/config/media/bitdash`.
* Fill in your Bitmovin API key.
  The API Key can be found [here](https://app.bitmovin.com/settings).
* Fill in your Bitdash player key.
  The Player Key can be found [here](https://app.bitmovin.com/player/overview).
* Save the form.

## Todo:

* Create upload possibility to external FTP environment.
* Adding CKEditor plugin for bitmovin.
