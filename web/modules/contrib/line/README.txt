
-- SUMMARY --

Integration module with SDK of the LINE Messaging API for PHP.

For a full description of the module, visit the project page:
  http://drupal.org/project/line
To submit bug reports and feature suggestions, or to track changes:
  http://drupal.org/project/issues/line

-- REQUIREMENTS --

* line-bot-sdk-php
  https://github.com/line/line-bot-sdk-php

-- INSTALLATION --

1. Execute 'composer update' from the site's root directory (this will download
   the line-bot-sdk-php sdk).

2. Install the module as usual, see http://drupal.org/node/70151 for further
   information.

-- CONFIGURATION --

* Configure user permissions at Administer >> User management >> Access
  control >> line module.

  Only users with the "administer line settings" permission are allowed to
  access the module configuration page.

* Enable the line-bot-sdk-php SDK at Administer >> Site
  configuration >> LINE.

-- CREDITS --

Authors:
* Eleo Basili (eleonel) - http://drupal.org/u/eleonel

This project has been sponsored by Spinetta (http://spinetta.tech).
