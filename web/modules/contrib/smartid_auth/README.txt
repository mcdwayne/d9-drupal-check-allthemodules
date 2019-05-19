ID-Card and Mobile-ID authentication module using smartid.ee service, for Drupal 8.



To get ID-Card authentication to work,


CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------
Estonian ID-Card authentication requires a custom server setup,
Using this module you can authenticate (oauth) with smartid.ee service and leverage their integration with
ID-Card and Mobile-ID authentication.

REQUIREMENTS
------------
Requires external library league/oauth2-client.


INSTALLATION
------------
 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/node/895232 for further information.
 * Point smartid.ee to your oauth url: example.com/smartid/oauth

CONFIGURATION
-------------
 * open /admin/config/smartid_auth/smartid_config and update client_id/client_secret provided by smartid.ee
 * add redirect url - the authenticated user will be redirected after successful authentication

 MAINTAINERS
 -----------

 Current maintainers:
  * Yevgeny Alianov (alianov) - https://www.drupal.org/u/alianov