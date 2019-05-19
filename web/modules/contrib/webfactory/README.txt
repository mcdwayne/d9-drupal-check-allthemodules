CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Webfactory aims to deploy and manage easily sites through a single Drupal
instance (master).

For now you are able to:
 * Configure & Deploy new sites through Backoffice.
 * Share very basic contents between master and satellite sites.


 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/webfactory
 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/webfactory


REQUIREMENTS
------------

This module requires the following modules:
 * HAL (Core)
 * HTTP Basic Authentication (Core)
 * RESTful Web Services (Core)
 * Serialization (Core)

This module requires the library php mcrypt.


INSTALLATION
------------

 * Copy/paste the install-site.php script to your Drupal root directory.
 * Install as you would normally install a contributed drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

 * Enable the Webfactory master on your main site.
 * Configure your DNS and web server to have your multisite installation.
 * Deploy satellite site through your main site backoffice.
 * The installation profiles used for deployment must have the webfactory slave
   module as dependency.
 * The anonymous user role must have the permission
   'restful get webfactory_slave:site'.


MAINTAINERS
-----------

Current maintainer:
 * Alan Moreau (dDoak) - https://www.drupal.org/user/626534
 * Tony Cabaye (tocab) - https://www.drupal.org/user/886920
 * Florent Torregrosa (Grimreaper) - https://www.drupal.org/user/2388214

This project has been sponsored by:
 * Smile - http://www.smile.fr
