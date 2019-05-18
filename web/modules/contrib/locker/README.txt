CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

The Locker is an site authentication module. This module uses session to
forbid site access, unless you login to gain access to the site.
It doesn’t replace Drupal authentication but just serves as additional layer
to hide your Drupal site from public. Its an alternative to HTTP Auth standard,
recommended to be used in case your server doesn’t support it
or you don’t have permission to set it up.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/locker

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/locker

REQUIREMENTS
------------

This module doesn't requires any extra module.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal/installing-modules-composer-dependencies
   for further information.

   ```sh
   composer require drupal/locker 8.x.*@dev
   composer require "drupal/locker ~8.1"
   ```

CONFIGURATION
-------------

Activate module in Administration » Modules » Locker -> Configure

 * Lock your Drupal site: Yes

 * Set Username/passwords or Passphrase

 * Submit

MAINTAINERS
-----------

This project has been sponsored by:

This module has been originally developed under the sponsorship of 
the Websolutions Agency (http://ws.agency).
