-- SUMMARY --

  Superselect uses the Tokenize2 jQuery plugin to make your <select> elements
  more user-friendly.


-- INSTALLATION --

  1. Download the Tokenize2 jQuery plugin
     (https://github.com/dragonofmercy/Tokenize2 version 1.2 or higher is recommended)
     and extract the file under "libraries".
  2. Download and enable the module.
  3. Configure at Administer > Configuration > User interface > Superselect
     (requires administer site configuration permission)

-- INSTALLATION VIA COMPOSER --
  It is assumed you are installing Drupal through Composer using the Drupal
  Composer facade. See https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#drupal-packagist

  The Chosen JavaScript library does not support composer so manual steps are
  required in order to install it through this method.

  First, copy the following snippet into your project's composer.json file so
  the correct package is downloaded:

  "repositories": [
    {
      "type": "package",
      "package": {
        "name": "dragonofmercy/Tokenize2",
        "version": "1.8.2",
        "type": "drupal-library",
        "dist": {
          "url": "https://github.com/dragonofmercy/Tokenize2/archive/master.zip",
          "type": "zip"
        },
        "require": {
            "composer/installers": "^1.2.0"
        }
      }
    }
  ]

  Next, the following snippet must be added into your project's composer.json
  file so the javascript library is installed into the correct location:

  "extra": {
      "installer-paths": {
          "libraries/{$name}": ["type:drupal-library"]
      }
  }

  If there are already 'repositories' and/or 'extra' entries in the
  composer.json, merge these new entries with the already existing entries.

  After that, run:

  $ composer require dragonofmercy/Tokenize2
  $ composer require drupal/super_select

  The first uses the manual entries you made to install the JavaScript library,
  the second adds the Drupal module.

  Note: the requirement on the library is not in the module's composer.json
  because that would cause problems with automated testing.

-- INSTALLATION VIA DRUSH --

  A Drush command is provided for easy installation of the Chosen plugin.

  drush superselectplugin

  The command will download the plugin and unpack it in "libraries".
  It is possible to add another path as an option to the command, but not
  recommended unless you know what you are doing.

  If you are using Composer to manage your site's dependencies,
  then the Superselect plugin will automatically be downloaded to `libraries/superselect`.

-- ACCESSIBILITY CONCERN --

  There are accessibility problems with the main library as identified here:
        https://github.com/dragonofmercy/Tokenize2/issues

-- NOTES --
 1. Super select works properly with multiple select dropdowns only.
 2. For Date Dropdown need to enable this feature.
 
-- Future Focuse --
 1. Fetching data from remote server.
 2. Enable the same feature for single select dropdown as well.
 
 -- MAINTAINERS --

Current maintainers:

 * Arulraj M(arulraj) - https://www.drupal.org/u/arulraj

Requires - Drupal 8
License - GPL (see LICENSE)

 
