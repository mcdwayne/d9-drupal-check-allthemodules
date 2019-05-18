INTRODUCTION
------------

The Konami Code is a cheat code that appeared in many Konami video games. The
Konami Code module makes it so that when users enter a given code on your
website, it invokes a certain action.

For a full description of the module, visit the project page:
  https://www.drupal.org/project/konamicode
To submit bug reports and feature suggestions, or to track changes:
  http://drupal.org/project/issues/konamicode
The full documentation can be found at:
  https://www.drupal.org/docs/8/modules/konami-code


REQUIREMENTS
------------

 * drupal-konamicode/raptorize
 * drupal-konamicode/flip_text
 * drupal-konamicode/image_spawn
 * drupal-konamicode/snowfall
 * drupal-konamicode/asteroids


RECOMMENDED MODULES
-------------------

None.


INSTALLATION
------------

It is recommended to install this module using composer, this will also download
all the dependencies on external libraries that this module has. To install the
module using composer execute: composer require drupal/konamikode

When developing on this module (meaning you did a git checkout) or when you
downloaded the module as a package from drupal.org, make sure to execute the
following composer commands:

 * composer require drupal-konamicode/raptorize:VERSION
 * composer require drupal-konamicode/flip_text:VERSION
 * composer require drupal-konamicode/image_spawn:VERSION
 * composer require drupal-konamicode/snowfall:VERSION
 * composer require drupal-konamicode/asteroids:VERSION

Please check for the latest versions the composer.json file of this module.


CONFIGURATION
-------------

You can manage all the actions and their specific configuration options from the
admin UI located at: /admin/config/user-interface/konamicode.


TROUBLESHOOTING
---------------

For the complete documentation about the module please have a look at:
 * https://www.drupal.org/docs/8/modules/konami-code


MAINTAINERS
-----------

Current maintainers:
 * Bram Driesen (BramDriesen) - https://www.drupal.org/u/BramDriesen
