
-- SUMMARY --

Integration module with Rakuten Web Service SDK for PHP

For a full description of the module, visit the project page:
  http://drupal.org/project/rakuten
To submit bug reports and feature suggestions, or to track changes:
  http://drupal.org/project/issues/rakuten

-- REQUIREMENTS --

* rws-php-sdk
  https://github.com/rakuten-ws/rws-php-sdk

-- INSTALLATION --

1. Execute 'composer update' from the site's root directory (this will download
   the rws-php-sdk sdk).

2. Install the module as usual, see http://drupal.org/node/70151 for further
   information.

-- CONFIGURATION --

* Configure user permissions at Administer >> User management >> Access
  control >> rakuten module.

  Only users with the "administer rakuten settings" permission are allowed to
  access the module configuration page.

* Enable the rws-php-sdk SDK at Administer >> Site
  configuration >> rakuten.

  -- USAGE --

* After the module configuration you can use the Rakuten API's in your custom modules.

* This module defines four variables that you may need to connect with your App: 
  rakuten_app_id, rakuten_app_secret, rakuten_affiliate_id and rakuten_domains.

  To access those variables you could to use the Simple Configuration API:
  https://www.drupal.org/node/1809490

-- CREDITS --

Authors:
* Eleo Basili (eleonel) - http://drupal.org/u/eleonel

This project has been sponsored by Spinetta (http://spinetta.tech).
